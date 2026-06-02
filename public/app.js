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
const imageFileInput = document.getElementById('product-image-file');
const imageInput = document.getElementById('product-image');
const imagePreview = document.getElementById('product-image-preview');
const imagePreviewText = document.getElementById('product-image-preview-text');
const existingImageInput = document.getElementById('product-image-existing');
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
document.getElementById('image-file-button').addEventListener('click', (e) => {
    e.preventDefault();
    imageFileInput.value = '';
    imageFileInput.click();
});

imageFileInput.addEventListener('change', handleImageFileChange);
imageInput.addEventListener('input', handleImageUrlChange);

function showStatus(message, success = true) {
    statusBox.textContent = message;
    statusBox.className = 'status ' + (success ? 'success' : 'error');
    statusBox.style.display = 'block';
    setTimeout(() => statusBox.style.display = 'none', 4500);
}

function updateImagePreview(source) {
    if (!source) {
        imagePreview.src = '';
        imagePreview.style.display = 'none';
        imagePreviewText.style.display = 'block';
        return;
    }

    imagePreviewText.style.display = 'none';
    imagePreview.style.display = 'block';
    imagePreview.src = source;
}

imagePreview.onerror = () => {
    imagePreview.style.display = 'none';
    imagePreviewText.style.display = 'block';
    imagePreviewText.textContent = 'Unable to load preview. Check the URL or file format.';
};

function handleImageFileChange() {
    const file = imageFileInput.files[0];
    if (file) {
        imageInput.value = file.name;
        const reader = new FileReader();
        reader.onload = () => updateImagePreview(reader.result);
        reader.readAsDataURL(file);
        return;
    }

    imageInput.value = '';
    updateImagePreview('');
}

function handleImageUrlChange() {
    const urlValue = imageInput.value.trim();
    if (urlValue && !imageFileInput.files.length) {
        imagePreviewText.textContent = 'Loading preview...';
        updateImagePreview(urlValue);
        return;
    }

    if (imageFileInput.files.length > 0) {
        handleImageFileChange();
        return;
    }

    if (urlValue) {
        updateImagePreview(urlValue);
    } else {
        updateImagePreview('');
    }
}

const apiKeyMeta = document.querySelector('meta[name="api-key"]');
const AUTH_API_KEY = apiKeyMeta ? apiKeyMeta.content : '';

function getAuthHeaders() {
    return AUTH_API_KEY ? { 'X-API-KEY': AUTH_API_KEY } : {};
}

function getRequestOptions(method, data) {
    const options = { method };
    if (data instanceof FormData) {
        options.body = data;
        options.headers = getAuthHeaders();
        return options;
    }
    options.headers = { 'Content-Type': 'application/json', ...getAuthHeaders() };
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
        const response = await fetch(getApiUrl(), { headers: getAuthHeaders() });
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
    imageFileInput.value = '';
    imageInput.value = '';
    imagePreviewText.textContent = 'No image selected.';
    existingImageInput.value = '';
    createDateInput.value = '';
    descriptionInput.value = '';
    currentProduct = null;
    submitButton.textContent = 'Save product';
    updateImagePreview('');
}

async function submitForm(event) {
    event.preventDefault();
    const productId = idInput.value.trim();
    const imageFile = imageFileInput.files[0];
    const imageUrl = imageInput.value.trim();
    const existingImage = existingImageInput.value || null;

    const formData = new FormData();
    formData.append('product_name', nameInput.value.trim());
    formData.append('price', parseFloat(priceInput.value) || 0);
    formData.append('quantity', parseInt(quantityInput.value, 10) || 0);
    formData.append('rating', parseFloat(ratingInput.value) || 0);
    formData.append('category_id', categoryInput.value.trim() ? parseInt(categoryInput.value, 10) : '');
    formData.append('create_date', toMysqlDateTime(createDateInput.value) || '');
    formData.append('description', descriptionInput.value.trim());

    if (imageFile) {
        formData.append('image', imageFile);
    } else if (imageUrl) {
        formData.append('image', imageUrl);
    } else if (productId && existingImage) {
        formData.append('image', existingImage);
    } else {
        formData.append('image', '');
    }

    if (productId) {
        formData.append('_method', 'PUT');
    }

    if (!formData.get('product_name')) {
        showStatus('Product name is required.', false);
        return;
    }

    if (productId && currentProduct) {
        const currentCategory = currentProduct.category_id === null ? '' : String(currentProduct.category_id);
        const currentImage = currentProduct.image || '';
        const currentCreateDate = toInputDateTime(currentProduct.create_date) || '';
        const newCategory = categoryInput.value.trim();
        const newCreateDate = createDateInput.value || '';
        const newImage = imageFile ? null : imageUrl;

        const unchanged =
            currentProduct.product_name === nameInput.value.trim() &&
            formatMoney(currentProduct.price) === formatMoney(parseFloat(priceInput.value) || 0) &&
            formatInteger(currentProduct.quantity) === formatInteger(parseInt(quantityInput.value, 10) || 0) &&
            formatMoney(currentProduct.rating) === formatMoney(parseFloat(ratingInput.value) || 0) &&
            currentCategory === newCategory &&
            currentCreateDate === newCreateDate &&
            (currentProduct.description || '') === descriptionInput.value.trim() &&
            (!imageFile && (newImage || '') === currentImage);

        if (unchanged) {
            showStatus('No changes detected. Product not updated.', true);
            return;
        }
    }

    try {
        const response = await fetch(getApiUrl(productId), getRequestOptions('POST', formData));
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
    imageFileInput.value = '';
    const sampleUrl = 'https://example.com/images/apple-juice.jpg';
    imageInput.value = sampleUrl;
    existingImageInput.value = '';
    createDateInput.value = '';
    descriptionInput.value = 'Made from fresh apples. Sweet and refreshing!';
    idInput.value = '';
    imagePreviewText.textContent = 'Loading preview...';
    updateImagePreview(sampleUrl);
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
        const response = await fetch(getApiUrl(productId), { method: 'DELETE', headers: getAuthHeaders() });
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
        const response = await fetch(getApiUrl(productId), { headers: getAuthHeaders() });
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
        imageFileInput.value = '';
        imageInput.value = product.image || '';
        existingImageInput.value = product.image || '';
        createDateInput.value = toInputDateTime(product.create_date);
        descriptionInput.value = product.description || '';
        submitButton.textContent = 'Update product';
        updateImagePreview(product.image || '');
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
        const response = await fetch(url, { headers: getAuthHeaders() });
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
