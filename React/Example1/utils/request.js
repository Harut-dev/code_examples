import fetch from 'dva/fetch';
import {onError} from 'utils/error';

const headers = {};
const payload = {};

function parseJSON(response) {
    try {
        const contentType = response.headers.get("content-type");
        if (contentType && contentType.indexOf("application/json") !== -1) {
            return response.json().then(res => {
                try {
                    return typeof res === 'string' ? JSON.parse(res) : res;
                } catch (ex) {
                    return [];
                }
            })
        } else {
            return response.text().then(res => {
                try {
                    return typeof res === 'string' ? JSON.parse(res) : res;
                } catch (ex) {
                    return [];
                }
            });
        }
    } catch (e) {
        return [];
    }
}

function checkStatus(response) {
    if (response.status >= 200 && response.status < 400) {
        return response;
    }

    const error = new Error(response.statusText);
    error.response = response;
    return Promise.reject(error);
}

const createRequest = (baseURL, headers = () => {
}, body = () => false, r = (url, params) => fetch(url, params)) =>
    (url, {headers: optionsHeaders, ...payload} = {}) =>
        r(baseURL + url, {
            ...payload,
            ...((body) => (body) ? {body} : {})(body(payload)),
            headers: {
                Accept: 'application/json',
                ...(payload.body instanceof FormData ? {} : {
                    'Content-Type': 'application/json; charset=utf-8',
                }),
                ...headers(),
                ...optionsHeaders,
            },
        })
            .then(checkStatus)
            .then(parseJSON, onError({
                ...headers,
                ...optionsHeaders,
            }));

const request = createRequest(API_URL, () => headers);
const dataRequest = createRequest(DATA_API_URL, () => {
}, ({body}) => JSON.stringify({
    ...payload,
    ...body,
}));

export {request, dataRequest, headers, payload};