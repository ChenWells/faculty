/**
 * HK Teachers 編輯器腳本
 * 
 * @package HK_Teachers
 * @author 陳富國 (Fu-Kuo Chen)
 * @copyright 2023-2024 陳富國 (Fu-Kuo Chen)
 * 
 * 本檔案為專有軟體的一部分，未經授權不得複製、修改或分發
 * This file is part of proprietary software and unauthorized copying, 
 * modification or distribution is prohibited
 */

/* global hkTeachersData */
(function(wp) {
    const { data, apiFetch } = wp;
    const { dispatch } = data;
    const { addQueryArgs } = wp.url;

    // 設置 REST API 的預設選項
    apiFetch.use(apiFetch.createRootURLMiddleware(hkTeachersData.restUrl));
    apiFetch.use(apiFetch.createNonceMiddleware(hkTeachersData.restNonce));

    // 初始化時預載入類型設定
    dispatch('core').addEntities([{
        name: 'teacher',
        kind: 'postType',
        baseURL: '/wp/v2/teachers',
        label: '教師資訊'
    }]);

    // 註冊自訂 store
    const DEFAULT_STATE = {
        meta: {},
        isSaving: false,
        hasError: false,
        errorMessage: ''
    };

    const actions = {
        setMeta(meta) {
            return {
                type: 'SET_META',
                meta
            };
        },
        setSaving(isSaving) {
            return {
                type: 'SET_SAVING',
                isSaving
            };
        },
        setError(error) {
            return {
                type: 'SET_ERROR',
                error
            };
        },
        async saveMeta(postId, meta) {
            try {
                dispatch('hk-teachers/meta').setSaving(true);
                const response = await apiFetch({
                    path: addQueryArgs(`/wp/v2/teachers/${postId}`, { context: 'edit' }),
                    method: 'POST',
                    data: { meta }
                });
                dispatch('hk-teachers/meta').setMeta(response.meta);
                dispatch('hk-teachers/meta').setError(null);
                return response;
            } catch (error) {
                dispatch('hk-teachers/meta').setError(error.message);
                throw error;
            } finally {
                dispatch('hk-teachers/meta').setSaving(false);
            }
        }
    };

    const store = data.registerStore('hk-teachers/meta', {
        reducer(state = DEFAULT_STATE, action) {
            switch (action.type) {
                case 'SET_META':
                    return {
                        ...state,
                        meta: action.meta
                    };
                case 'SET_SAVING':
                    return {
                        ...state,
                        isSaving: action.isSaving
                    };
                case 'SET_ERROR':
                    return {
                        ...state,
                        hasError: action.error !== null,
                        errorMessage: action.error
                    };
                default:
                    return state;
            }
        },

        actions,

        selectors: {
            getMeta(state) {
                return state.meta;
            },
            isSaving(state) {
                return state.isSaving;
            },
            hasError(state) {
                return state.hasError;
            },
            getErrorMessage(state) {
                return state.errorMessage;
            }
        },

        controls: {
            FETCH_FROM_API(action) {
                return apiFetch({
                    path: addQueryArgs(action.path, { context: 'edit' })
                });
            }
        },

        resolvers: {
            * getMeta(postId) {
                try {
                    const post = yield actions.fetchFromAPI(`/wp/v2/teachers/${postId}`);
                    return actions.setMeta(post.meta);
                } catch (error) {
                    return actions.setError(error.message);
                }
            }
        }
    });
})(window.wp); 