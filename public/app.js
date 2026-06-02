const pageFolder = window.location.pathname
    .replace(/\/index\.php$/, '')
    .replace(/\/api\.php.*$/, '')
    .replace(/\/$/, '');
const API_URL = `${pageFolder}/api.php?path=products`;

const form = document.getElementById('product-form');
const submitButton = form.querySelector('button[type="submit"]');
const idInput = document.getElementById('product-id');
const nameInput = document.getElementById('product-name');
const priceInput = document.getElementById('product-price');
const quantityInput = document.getElementById('product-quantity');
const ratingInput = document.getElementById('product-rating');
const categoryInput = document.getElementById('product-category');
const imageInput = document.getElementById('product-image');
const createDateInput = document.getElementById('product-create-date');
const descriptionInput = document.getElementById('product-description');
const statusBox = document.getElementById('status');
const tableBody = document.getElementById('products-table-body');
const resetButton = document.getElementById('reset-button');
const sampleButton = document.getElementById('sample-button');
const refreshButton = document.getElementById('refresh-button');
const searchForm = document.getElementById('search-form');
const searchQuery = document.getElementById('search-query');
const clearSearchButton = document.getElementById('clear-search-button');
let currentProduct = null;

function showStatus(message, success = true) {
    statusBox.textContent = message;
    statusBox.className = 'status ' + (success ? 'success' : 'error');
    statusBox.style.display = 'block';
    setTimeout(() => statusBox.style.display = 'none', 4500);
}

function getRequestOptions(method, data) {
    const options = { method, headers: { 'Content-Type': 'application/json' } };
    if (data) options.body = JSON.stringify(data);
    return options;
}

function getApiUrl(productId = '') {
    return productId ? `${API_URL}/${productId}` : API_URL;
}

async function readJsonResponse(response) {
    const text = await response.text();

    try {
        return JSON.parse(text);
    } catch (error) {
        throw new Error(`API did not return JSON. Check this URL: ${response.url}`);
    }
}

function getErrorMessage(body, fallbackMessage) {
    if (body && body.error) {
        const message = body.message || fallbackMessage;
        return `${message}: ${body.error}`;
    }

    return body && body.message ? body.message : fallbackMessage;
}

async function fetchProducts() {
    try {
        const response = await fetch(getApiUrl());
        const products = await readJsonResponse(response);

        if (!response.ok) {
            throw new Error(getErrorMessage(products, 'Failed to load products'));
        }

        renderTable(products);
    } catch (error) {
        showStatus(error.message, false);
    }
}

function renderTable(products) {
    tableBody.innerHTML = '';
    if (!Array.isArray(products) || products.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="11">No products found.</td></tr>';
        return;
    }

    products.forEach(product => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${product.product_id}</td>
            <td>${escapeHtml(product.product_name)}</td>
            <td>${formatMoney(product.price)}</td>
            <td>${formatInteger(product.quantity)}</td>
            <td>${formatMoney(product.amount)}</td>
            <td>${formatMoney(product.rating)}</td>
            <td>${escapeHtml(product.category_id || '')}</td>
            <td>${renderImageValue(product.image)}</td>
            <td>${escapeHtml(product.description || '')}</td>
            <td>${escapeHtml(product.create_date || '')}</td>
            <td>
                <button type="button" class="secondary" data-action="edit" data-id="${product.product_id}">Edit</button>
                <button type="button" class="danger" data-action="delete" data-id="${product.product_id}">Delete</button>
            </td>
        `;
        tableBody.appendChild(row);
    });
}

function formatMoney(value) {
    const number = Number(value);
    return Number.isFinite(number) ? number.toFixed(2) : '0.00';
}

function formatInteger(value) {
    const number = Number(value);
    return Number.isFinite(number) ? String(number) : '0';
}

function renderImageValue(value) {
    if (!value) {
        return '';
    }

    const image = escapeHtml(value);
    return `<a href="${image}" target="_blank" rel="noopener">View</a>`;
}

function toMysqlDateTime(value) {
    return value ? value.replace('T', ' ') + ':00' : null;
}

function toInputDateTime(value) {
    return value ? String(value).replace(' ', 'T').slice(0, 16) : '';
}

function escapeHtml(text) {
    return String(text || '').replace(/[&<>"']/g, c => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
    }[c]));
}

function resetForm() {
    idInput.value = '';
    nameInput.value = '';
    priceInput.value = '';
    quantityInput.value = '';
    ratingInput.value = '';
    categoryInput.value = '';
    imageInput.value = '';
    createDateInput.value = '';
    descriptionInput.value = '';
    currentProduct = null;
    submitButton.textContent = 'Save product';
}

async function submitForm(event) {
    event.preventDefault();
    const productId = idInput.value.trim();
    const payload = {
        product_name: nameInput.value.trim(),
        price: parseFloat(priceInput.value) || 0,
        quantity: parseInt(quantityInput.value, 10) || 0,
        rating: parseFloat(ratingInput.value) || 0,
        category_id: categoryInput.value.trim() ? parseInt(categoryInput.value, 10) : null,
        image: imageInput.value.trim() || null,
        create_date: toMysqlDateTime(createDateInput.value),
        description: descriptionInput.value.trim(),
    };

    if (!payload.product_name) {
        showStatus('Product name is required.', false);
        return;
    }

    try {
        if (productId && currentProduct) {
            const currentCreateDate = toInputDateTime(currentProduct.create_date);
            const currentCategory = currentProduct.category_id === null ? null : String(currentProduct.category_id);
            const newCategory = payload.category_id === null ? null : String(payload.category_id);

            const unchanged =
                currentProduct.product_name === payload.product_name &&
                Number(currentProduct.price) === payload.price &&
                Number(currentProduct.quantity) === payload.quantity &&
                Number(currentProduct.rating) === payload.rating &&
                currentCategory === newCategory &&
                (currentProduct.image || '') === (payload.image || '') &&
                currentCreateDate === createDateInput.value &&
                (currentProduct.description || '') === (payload.description || '');

            if (unchanged) {
                showStatus('No changes detected. Product not updated.', true);
                return;
            }
        }

        const method = productId ? 'PUT' : 'POST';
        const response = await fetch(getApiUrl(productId), getRequestOptions(method, payload));
        const body = await readJsonResponse(response);

        if (!response.ok) {
            throw new Error(getErrorMessage(body, 'Failed to save product'));
        }

        resetForm();
        fetchProducts();
        showStatus(productId ? 'Product updated successfully.' : 'Product created successfully.');
    } catch (error) {
        showStatus(error.message, false);
    }
}

function addSampleProduct() {
    nameInput.value = 'Apple Juice';
    priceInput.value = '12.99';
    quantityInput.value = '3';
    ratingInput.value = '4.50';
    categoryInput.value = '1';
    imageInput.value = 'https://example.com/images/apple-juice.jpg';
    createDateInput.value = '';
    descriptionInput.value = 'Made from fresh apples. Sweet and refreshing!';
    idInput.value = '';
}

async function handleTableClick(event) {
    const button = event.target.closest('button');
    if (!button) return;
    const action = button.dataset.action;
    const productId = button.dataset.id;

    if (action === 'edit') {
        await editProduct(productId);
        return;
    }

    if (action === 'delete') {
        await deleteProduct(productId);
    }
}

async function deleteProduct(productId) {
    if (!confirm('Delete this product?')) {
        return;
    }

    try {
        const response = await fetch(getApiUrl(productId), { method: 'DELETE' });
        const body = await readJsonResponse(response);

        if (!response.ok) {
            throw new Error(getErrorMessage(body, 'Failed to delete product'));
        }

        resetForm();
        fetchProducts();
        showStatus('Product deleted successfully.');
    } catch (error) {
        showStatus(error.message, false);
    }
}

async function editProduct(productId) {
    try {
        const response = await fetch(getApiUrl(productId));
        const product = await readJsonResponse(response);

        if (!response.ok) {
            throw new Error(getErrorMessage(product, 'Product not found'));
        }

        currentProduct = product;
        idInput.value = product.product_id;
        nameInput.value = product.product_name || '';
        priceInput.value = formatMoney(product.price);
        quantityInput.value = formatInteger(product.quantity);
        ratingInput.value = formatMoney(product.rating);
        categoryInput.value = product.category_id || '';
        imageInput.value = product.image || '';
        createDateInput.value = toInputDateTime(product.create_date);
        descriptionInput.value = product.description || '';
        submitButton.textContent = 'Update product';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } catch (error) {
        showStatus(error.message, false);
    }
}

async function searchProducts(query) {
    const trimmedQuery = query.trim();
    if (!trimmedQuery) {
        showStatus('Please enter a search query.', false);
        return;
    }

    try {
        const url = `${pageFolder}/api.php?path=products/search&q=${encodeURIComponent(trimmedQuery)}`;
        const response = await fetch(url);
        const products = await readJsonResponse(response);

        // Check if response is an error message object
        if (products && products.message && !Array.isArray(products)) {
            showStatus(products.message, false);
            renderTable([]);
            return;
        }

        if (!response.ok) {
            throw new Error(getErrorMessage(products, 'Search failed'));
        }

        if (!Array.isArray(products) || products.length === 0) {
            renderTable([]);
            showStatus(`No products found matching "${trimmedQuery}".`);
            return;
        }

        renderTable(products);
        showStatus(`Found ${products.length} product(s).`);
    } catch (error) {
        showStatus(error.message, false);
        renderTable([]);
    }
}

function clearSearch() {
    searchQuery.value = '';
    fetchProducts();
    showStatus('Search cleared. Showing all products.');
}

form.addEventListener('submit', submitForm);
resetButton.addEventListener('click', resetForm);
sampleButton.addEventListener('click', addSampleProduct);
refreshButton.addEventListener('click', fetchProducts);
searchForm.addEventListener('submit', (e) => {
    e.preventDefault();
    searchProducts(searchQuery.value);
});
clearSearchButton.addEventListener('click', clearSearch);
tableBody.addEventListener('click', handleTableClick);

fetchProducts();
