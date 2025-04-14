/**
 * HK Faculty前端JavaScript
 * 
 * @package HK_Teachers
 * @author 陳富國 (Fu-Kuo Chen)
 * @copyright 2023-2024 陳富國 (Fu-Kuo Chen)
 * 
 * 本檔案為專有軟體的一部分，未經授權不得複製、修改或分發
 * This file is part of proprietary software and unauthorized copying, 
 * modification or distribution is prohibited
 */

jQuery(document).ready(function($) {
    // 檢測是否為移動設備
    let isMobile = window.matchMedia("(max-width: 768px)").matches;
    let activeContainer = null; // 追蹤當前正在hover的容器
    let isResizing = false; // 防止resize事件過度觸發
    let mouseLeavingTimer = null; // 追蹤滑鼠離開事件的計時器

    // 處理主題設置
    function initTheme() {
        // 檢查是否有主題設置
        if (typeof hkfSettings !== 'undefined' && hkfSettings.themeMode === 'dark') {
            // 如果使用者設置了暗色主題，但頁面沒有相應的 class，添加它
            if (!$('body').hasClass('theme-dark')) {
                $('body').addClass('theme-dark');
            }
        }
    }

    // 初始化accordion功能
    function initAccordion() {
        const accordionContainers = $('.accordion-container');
        
        // 移除所有現有事件，防止重複綁定
        accordionContainers.off('mouseenter mouseleave');
        accordionContainers.find('.accordion-header').off('click');
        $('.accordion-close').off('click');
        $(document).off('click.accordion');
        
        // 檢查URL片段是否指向特定accordion
        const hash = window.location.hash;
        if (hash) {
            const targetAccordion = $(hash);
            if (targetAccordion.length && targetAccordion.hasClass('accordion-container')) {
                openAccordion(targetAccordion);
                targetAccordion.addClass('clicked'); // 標記為已點擊
                scrollToElement(targetAccordion);
            }
        }

        // 所有設備的關閉按鈕處理
        $(document).on('click', '.accordion-close', function(e) {
            e.preventDefault();
            e.stopPropagation(); // 阻止事件冒泡到標題區域
            
            const container = $(this).closest('.accordion-container');
            container.removeClass('clicked active');
            
            // 如果在桌面上且滑鼠仍在容器上，則重新添加active狀態（但不是clicked狀態）
            if (!isMobile && container.is(':hover')) {
                container.addClass('active');
            }
        });

        // 桌面設備的hover處理
        if (!isMobile) {
            accordionContainers.on('mouseenter', function() {
                // 清除任何現有的離開計時器
                if (mouseLeavingTimer) {
                    clearTimeout(mouseLeavingTimer);
                    mouseLeavingTimer = null;
                }
                
                // 如果不是已點擊固定狀態，則hover時展開
                if (!$(this).hasClass('clicked')) {
                    activeContainer = $(this);
                    openAccordion($(this));
                }
            });

            accordionContainers.on('mouseleave', function() {
                const container = $(this);
                
                // 延遲收摺，避免滑鼠在容器內快速移動時觸發
                mouseLeavingTimer = setTimeout(function() {
                    // 如果不是已點擊固定狀態，則離開時關閉
                    if (!container.hasClass('clicked') && container.hasClass('active')) {
                        closeAccordion(container);
                        if (activeContainer === container[0]) {
                            activeContainer = null;
                        }
                    }
                    mouseLeavingTimer = null;
                }, 300); // 300毫秒的延遲
            });
        }

        // 所有設備的點擊標題處理
        $(document).on('click', '.accordion-header', function(e) {
            // 獲取當前點擊的accordion容器
            const container = $(this).closest('.accordion-container');
            
            // 檢查點擊的是否為關閉按鈕
            if($(e.target).hasClass('accordion-close')) {
                container.removeClass('active');
                container.find('.accordion-content').slideUp(200);
                container.find('.accordion-icon').text('+');
                return false; // 阻止冒泡
            }
            
            // 切換當前accordion的狀態
            container.toggleClass('active');
            container.find('.accordion-content').slideToggle(200);
            
            // 更新加號/減號圖標
            if(container.hasClass('active')) {
                container.find('.accordion-icon').text('-');
                container.find('.accordion-close').css({
                    'display': 'flex',
                    'opacity': '0.7',
                    'visibility': 'visible'
                });
            } else {
                container.find('.accordion-icon').text('+');
            }
        });

        // 處理點擊容器外部時關閉accordion
        $(document).on('click.accordion', function(e) {
            const clickedOutside = !$(e.target).closest('.accordion-container').length;
            
            if (clickedOutside) {
                // 關閉所有非固定展開的accordion
                $('.accordion-container').each(function() {
                    const container = $(this);
                    if (container.hasClass('active') && !container.hasClass('clicked')) {
                        closeAccordion(container);
                    }
                });
            }
        });

        // 當鼠標離開accordion容器且未開啟時，確保關閉按鈕隱藏
        $(document).on('mouseleave', '.accordion-container', function() {
            if(!$(this).hasClass('active')) {
                $(this).find('.accordion-close').css({
                    'opacity': '0',
                    'visibility': 'hidden'
                });
            }
        });
    }

    // 打開accordion
    function openAccordion(container) {
        container.addClass('active');
        
        // 確保關閉按鈕顯示
        container.find('.accordion-close').css({
            'opacity': '0.7',
            'visibility': 'visible'
        });
    }

    // 關閉accordion
    function closeAccordion(container) {
        container.removeClass('active');
        
        // 如果不是hover狀態，隱藏關閉按鈕
        if (!container.is(':hover')) {
            container.find('.accordion-close').css({
                'opacity': '0',
                'visibility': 'hidden'
            });
        }
    }

    // 滾動到元素位置
    function scrollToElement(element) {
        $('html, body').animate({
            scrollTop: element.offset().top - 100
        }, 500);
    }

    // 處理窗口大小變化
    $(window).on('resize', function() {
        if (!isResizing) {
            isResizing = true;
            setTimeout(function() {
                const wasMobile = isMobile;
                isMobile = window.matchMedia("(max-width: 768px)").matches;
                
                // 如果設備類型改變，重新綁定事件
                if (wasMobile !== isMobile) {
                    // 重新初始化accordion
                    initAccordion();
                }
                
                isResizing = false;
            }, 250);
        }
    });

    // 初始化
    initTheme();
    initAccordion();

    // 工作職掌模態窗口功能
    const modal = $('#staff-modal');
    const modalBody = modal.find('.staff-modal-body');
    const modalClose = modal.find('.staff-modal-close');
    const modalTitle = modal.find('.staff-modal-title');
    
    // 點擊查看工作職掌按鈕
    $(document).on('click', '.staff-resp-btn', function(e) {
        e.preventDefault();
        
        // 獲取職員ID和名稱
        const staffId = $(this).data('staff-id');
        const staffName = $(this).closest('.staff-item, .staff-info').find('.staff-name, .staff-title').text().trim();
        const contentId = 'staff-modal-' + staffId;
        const respContent = $('#' + contentId).find('.staff-resp-content').html();
        
        // 設置模態窗口標題和內容
        modalTitle.text(staffName + ' - 工作職掌');
        modalBody.html(respContent);
        
        // 顯示模態窗口
        modal.css('display', 'flex');
        setTimeout(function() {
            modal.addClass('show');
        }, 10);
        
        // 防止頁面滾動
        $('body').css('overflow', 'hidden');
    });
    
    // 關閉模態窗口
    function closeModal() {
        modal.removeClass('show');
        setTimeout(function() {
            modal.css('display', 'none');
            $('body').css('overflow', '');
        }, 300);
    }
    
    // 點擊關閉按鈕
    modalClose.on('click', function() {
        closeModal();
    });
    
    // 點擊模態窗口背景關閉
    modal.on('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });
    
    // ESC鍵關閉模態窗口
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && modal.hasClass('show')) {
            closeModal();
        }
    });
}); 