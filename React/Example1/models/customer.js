import {isEmpty} from 'lodash';
import {
    getList, getCustomers, getCustomer, customerCreate, customerUpdate, customerDelete,
    setCustomerStatus,
} from 'services/customer';
import {routerRedux} from 'dva/router';
import {getWatcher} from '../utils/watcher';

export const Customer = {
    namespace: 'customer',
    state: {
        list: [],
        customers: [],
        isLoading: false,
        entry: {},
        isLoadingEntry: false,
        isUpdating: false,
    },
    reducers: {
        SET_STATE: (state, action) => ({...state, ...action.payload}),
    },
    effects: {
        * ALL({force = false, filter = {}, expand = ''}, {
            call, put, select,
        }) {
            let list = yield select(({customer: {list}}) => list);
            if (isEmpty(list) || force) {
                yield put({
                    type: 'SET_STATE',
                    payload: {
                        isLoading: true,
                    },
                });
                list = yield call(getList, filter, expand);
                yield put({
                    type: 'SET_STATE',
                    payload: {
                        list,
                        isLoading: false,
                    },
                });
            }
            return list;
        },
        * GET_CUSTOMERS({
                            payload: {
                                sort = '', fields = '', currentPage = '', perPage = 1000,
                            },
                        }, {
                            call, put,
                        }) {
            yield put({
                type: 'SET_STATE',
                payload: {
                    isLoading: true,
                },
            });
            const customers = yield call(getCustomers, sort, perPage, currentPage, fields);
            yield put({
                type: 'SET_STATE',
                payload: {
                    customers,
                    isLoading: false,
                },
            });
        },
        * GET_CUSTOMER({payload}, {put, call}) {
            yield put({
                type: 'SET_STATE',
                payload: {
                    isLoadingEntry: true,
                },
            });
            const entry = yield call(getCustomer, payload.id);
            yield put({
                type: 'SET_STATE',
                payload: {
                    entry,
                    isLoadingEntry: false,
                },
            });
            return entry;
        },
        * CREATE({payload}, {call, put}) {
            console.log('create');
            try {
                yield put({
                    type: 'SET_STATE',
                    payload: {
                        isUpdating: true,
                    },
                });
                const response = yield call(customerCreate, payload);
                yield put({
                    type: 'SET_STATE',
                    payload: {
                        isUpdating: false,
                    },
                });
                yield put(routerRedux.push('/admin/customers'));
                return response;
            } catch (e) {
                console.log(e);
            }
        },
        * UPDATE({payload}, {call, put}) {
            try {
                yield put({
                    type: 'SET_STATE',
                    payload: {
                        isUpdating: true,
                    },
                });
                const response = yield call(customerUpdate, payload);
                yield put({
                    type: 'SET_STATE',
                    payload: {
                        isUpdating: false,
                    },
                });
                yield put(routerRedux.push('/admin/customers'));
                return response;
            } catch (e) {
                console.log(e);
            }
        },
        * DELETE({payload}, {call, put}) {
            try {
                yield call(customerDelete, payload.id);
            } catch (e) {
                console.log(e);
            }
            yield put(routerRedux.push('/admin/customers'));
        },
        * SET_CUSTOMER_STATUS({payload}, {put, call, all}) {
            yield call(setCustomerStatus, payload.id, payload.status);
            yield put({
                type: 'GET_CUSTOMERS',
                payload: {
                    sort: 'company',
                    fields: 'id,company,name,authToken,tiles,reports,other,status',
                },
            });
        },
        editWatcher: getWatcher('/admin/customers/edit/:id', function* ({put}, payload) {
            yield put({
                type: 'GET_CUSTOMER',
                payload,
            });
        }),
        pageWatcher: getWatcher('/admin/customers', function* ({put, all}) {
            yield all([
                put({
                    type: 'SET_STATE',
                    payload: {
                        entry: {},
                    },
                }),
                put({
                    type: 'GET_CUSTOMERS',
                    payload: {
                        sort: 'company',
                        fields: 'id,company,name,authToken,tiles,reports,other,status',
                    },
                }),
            ]);
        }),
    },
    subscriptions: {},
};
