import {request} from 'utils/request';

export async function getList(filter = {}, expand = '') {
    const filterJSON = JSON.stringify(filter);
    return (await request(`customers?expand=${expand}&filter=${filterJSON}`));
}

export async function getCustomers(sort, perPage, currentPage, fields) {
    return (await request(`customers?sort=${sort}&per-page=${perPage}&currentPage=${currentPage}&fields=${fields}`));
}

export async function getCustomer(id) {
    return (await request(`customers/${id}`));
}

export async function customerCreate(data) {
    return await request('customers', {
        method: 'POST',
        body: JSON.stringify(data),
    });
}

export async function customerUpdate(data) {
    return await request(`customers/${data.id}`, {
        method: 'PUT',
        body: JSON.stringify(data),
    });
}

export async function customerDelete(id) {
    return await request(`customers/${id}`, {
        method: 'DELETE',
    });
}

export async function setCustomerStatus(id, status) {
    return await request(`customers/${id}`, {
        method: 'PUT',
        body: JSON.stringify({status: status}),
    });
}