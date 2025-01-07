<!DOCTYPE html>
<html>

<head>
    <title>App</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous">
    </script>
</head>

<body>
    <main>
        <header>
            <div class="px-3 py-2 text-bg-dark border-bottom">
                <div class="container">
                    <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-lg-start">
                        <ul class="nav col-12 col-lg-auto my-2 justify-content-center my-md-0 text-small">
                            <li>
                                <a href="#" class="nav-link text-white">
                                    Stock Management Portal
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="px-3 py-2 border-bottom mb-3">
                <div class="container d-flex flex-wrap justify-content-center">
                    <form class="d-flex gap-2 col-12 col-lg-auto mb-2 mb-lg-0 me-lg-auto align-items-center" id="submit-form" method="POST">
                        <input type="text" required id="product_name" name="product_name" class="form-control min-w-25" placeholder="Product Name" />
                        <input type="number" required min="0" id="quantity_in_stock" name="quantity_in_stock" class="form-control min-w-25" placeholder="Quantity in Stock" />
                        <input type="number" step="0.01" required min="1" id="price_per_item" name="price_per_item" class="form-control min-w-25" placeholder="Price per item ($)" />
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </form>
                </div>
            </div>
        </header>

        <div class="container">
            <div id="products-container">
                <h3>Products</h3>
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Quantity in Stock</th>
                            <th>$ Price per item</th>
                            <th>Datetime submitted</th>
                            <th>$ Total value number</th>
                        </tr>
                    </thead>
                    <tbody id="products-table-body">
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            var productsData = [];
            getProducts();

            $('#submit-form').submit(function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const data = Object.fromEntries(formData.entries());
                
                $.ajax({
                    url: '/api/store-product',
                    type: 'POST',
                    data: data,
                    headers: {
                        'Accept': 'application/json',
                    },
                    success: function(response) {
                        getProducts();
                        $('#submit-form')[0].reset();
                        alert('Product added successfully');
                    },
                    error: function(xhr, status, error) {
                        const response = JSON.parse(xhr.responseText);
                        alert(response.message);
                    }
                });

            });

            function getProducts() {
                $.ajax({
                    url: '/api/get-products',
                    type: 'GET',
                    success: function(response) {
                        productsData = response;
                        setProductsTable();
                    }
                });
            }

            function setProductsTable() {
                const products = productsData.products;
                const tableBody = $('#products-table-body');
                tableBody.empty();
                products.forEach(product => {
                    tableBody.append(`<tr><td contenteditable="true" data-id="${product['id']}" data-field="product_name" style="cursor: pointer;">${product['product_name']}</td><td contenteditable="true" data-id="${product['id']}" data-field="quantity_in_stock" style="cursor: pointer;">${product['quantity_in_stock']}</td><td contenteditable="true" data-id="${product['id']}" data-field="price_per_item" style="cursor: pointer;">${product['price_per_item']}</td><td>${product['datetime_submitted']}</td><td>${product['total_value']}</td></tr>`);
                });

                tableBody.append(`<tr><td colspan="4">Total value</td><td>$ ${productsData.total_value}</td></tr>`);
            }

            $('#products-table-body').on('blur', 'td[contenteditable="true"]', function() {
                const id = $(this).data('id');
                const field = $(this).data('field');
                const value = $(this).text();

                // Creating a map of products for faster searching
                const productMap = {};
                productsData.products.forEach(product => {
                    productMap[product['id']] = product;
                });

                const product = productMap[id];

                // Skipping update if the value is the same as the current value
                if (product[field] === value) {
                    return;
                }

                $.ajax({
                    url: `/api/update-product/${id}`,
                    type: 'PUT',
                    data: {
                        field: field,
                        value: value
                    },
                    headers: {
                        'Accept': 'application/json',
                    },
                    success: function(response) {
                        getProducts();
                    },
                    error: function(xhr, status, error) {
                        setProductsTable();

                        const response = JSON.parse(xhr.responseText);
                        alert(response.message);
                    }
                });
            });

            $('#products-table-body').on('keydown', 'td[contenteditable="true"]', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault(); // Prevent newline in the cell
                    $(this).blur(); // Trigger blur event to update
                }
            });
        });
    </script>
</body>

</html>