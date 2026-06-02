<?php

declare(strict_types=1);

function currentPath()
{
    $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
    $scriptFolder = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $basePath = $scriptFolder === '/' ? '' : $scriptFolder;

    return '/' . ltrim((string) substr($requestPath, strlen($basePath)), '/');
}

function isApiRequest($path)
{
    return strpos($path, '/api') === 0 || strpos($path, '/api.php') === 0;
}

if (isApiRequest(currentPath())) {
    require __DIR__ . '/api.php';
    exit;
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CRUD Web API Interface</title>
    <link rel="icon" href="favicon.svg" type="image/svg+xml" />
    <link rel="stylesheet" href="styles.css" />
</head>
<body>
    <header>
        <div class="brand">
            <div class="logo-icon">API</div>
            <div>
                <h1>CRUD Web API Tester</h1>
                <p>Use this page to create, update, delete, and inspect products through the API.</p>
            </div>
        </div>
    </header>

    <main>
        <section class="card">
            <h2>Product form</h2>
            <form id="product-form">
                <input type="hidden" id="product-id" />
                <div>
                    <label for="product-name">Name</label>
                    <input id="product-name" type="text" placeholder="Product name" required />
                </div>
                <div>
                    <label for="product-price">Price</label>
                    <input id="product-price" type="number" step="0.01" min="0" placeholder="0.00" required />
                </div>
                <div>
                    <label for="product-quantity">Quantity</label>
                    <input id="product-quantity" type="number" min="0" step="1" placeholder="0" required />
                </div>
                <div>
                    <label for="product-rating">Rating</label>
                    <input id="product-rating" type="number" min="0" max="5" step="0.01" placeholder="0.00" />
                </div>
                <div>
                    <label for="product-category">Category ID</label>
                    <input id="product-category" type="number" min="0" step="1" placeholder="Category ID" />
                </div>
                <div>
                    <label for="product-image">Image</label>
                    <input id="product-image" type="text" maxlength="100" placeholder="image.jpg or image URL" />
                </div>
                <div>
                    <label for="product-create-date">Create Date</label>
                    <input id="product-create-date" type="datetime-local" />
                </div>
                <div class="full-width">
                    <label for="product-description">Description</label>
                    <textarea id="product-description" placeholder="Product description"></textarea>
                </div>
                <div class="actions">
                    <button type="submit" class="primary">Save product</button>
                    <button type="button" id="reset-button" class="secondary">Reset form</button>
                    <button type="button" id="sample-button" class="secondary">Add sample product</button>
                </div>
            </form>
            <div id="status" class="status"></div>
        </section>

        <section class="card">
            <h2>Search products</h2>
            <form id="search-form">
                <div>
                    <label for="search-query">Search by ID or Name</label>
                    <input id="search-query" type="text" placeholder="Enter product ID or name" />
                </div>
                <div class="actions">
                    <button type="submit" class="primary">Search</button>
                    <button type="button" id="clear-search-button" class="secondary">Clear search</button>
                </div>
            </form>
        </section>

        <section class="card">
            <div class="section-header">
                <div>
                    <h2>Product list</h2>
                    <p>Reloads automatically after create, update, or delete.</p>
                </div>
                <button type="button" id="refresh-button" class="secondary">Refresh list</button>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Amount</th>
                            <th>Rating</th>
                            <th>Category</th>
                            <th>Image</th>
                            <th>Description</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="products-table-body"></tbody>
                </table>
            </div>
        </section>
    </main>

    <script src="app.js"></script>
</body>
</html>
