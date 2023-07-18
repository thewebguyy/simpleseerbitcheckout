<?php
// Handle the merchant store functionality

// Example product data
$products = [
    [
        'id' => 1,
        'name' => 'Product 1',
        'price' => 50.00,
        'category' => 'Category 1',
        'description' => 'This is product 1 description.',
    ],
    [
        'id' => 2,
        'name' => 'Product 2',
        'price' => 75.00,
        'category' => 'Category 2',
        'description' => 'This is product 2 description.',
    ],
    [
        'id' => 3,
        'name' => 'Product 3',
        'price' => 100.00,
        'category' => 'Category 1',
        'description' => 'This is product 3 description.',
    ],
];

// Process any actions or form submissions related to the merchant store functionality

// Get unique categories from the product data
$categories = array_unique(array_column($products, 'category'));

// Filter products by category if a category is selected
if (isset($_GET['category']) && in_array($_GET['category'], $categories)) {
    $selectedCategory = $_GET['category'];
    $filteredProducts = array_filter($products, function ($product) use ($selectedCategory) {
        return $product['category'] === $selectedCategory;
    });
} else {
    $selectedCategory = null;
    $filteredProducts = $products;
}

// Filter products by price range if a minimum and maximum price are provided
if (isset($_GET['min_price']) && isset($_GET['max_price'])) {
    $minPrice = floatval($_GET['min_price']);
    $maxPrice = floatval($_GET['max_price']);
    $filteredProducts = array_filter($filteredProducts, function ($product) use ($minPrice, $maxPrice) {
        return $product['price'] >= $minPrice && $product['price'] <= $maxPrice;
    });
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Merchant Store</title>
    <style>
        /* Add your CSS styles for the merchant store */
        /* This is just a basic example */
        .product {
            border: 1px solid #ccc;
            padding: 10px;
            margin-bottom: 20px;
        }

        .product h3 {
            margin-top: 0;
        }

        .product .price {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>Welcome to the Merchant Store</h1>
    
    <div class="categories">
        <h3>Categories</h3>
        <ul>
            <li><a href="merchantstore.php">All</a></li>
            <?php foreach ($categories as $category) : ?>
                <li><a href="merchantstore.php?category=<?php echo urlencode($category); ?>"><?php echo $category; ?></a></li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="filter">
        <h3>Filter by Price</h3>
        <form action="merchantstore.php" method="get">
            <label for="min_price">Min Price:</label>
            <input type="number" id="min_price" name="min_price" step="0.01" value="<?php echo isset($_GET['min_price']) ? $_GET['min_price'] : ''; ?>">
            <label for="max_price">Max Price:</label>
            <input type="number" id="max_price" name="max_price" step="0.01" value="<?php echo isset($_GET['max_price']) ? $_GET['max_price'] : ''; ?>">
            <input type="submit" value="Apply">
        </form>
    </div>

    <div class="products">
        <?php if ($selectedCategory) : ?>
            <h2><?php echo $selectedCategory; ?></h2>
        <?php endif; ?>

        <?php if (count($filteredProducts) > 0) : ?>
            <?php foreach ($filteredProducts as $product) : ?>
                <div class="product">
                    <h3><?php echo $product['name']; ?></h3>
                    <p class="price">$<?php echo $product['price']; ?></p>
                    <p><?php echo $product['description']; ?></p>
                    <!-- Add an "Add to Cart" button for each product -->
                    <form action="cart.php" method="post">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <input type="number" name="quantity" value="1" min="1">
                        <input type="submit" value="Add to Cart">
                    </form>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <p>No products found.</p>
        <?php endif; ?>
    </div>

    <!-- Add your merchant store content here -->
</body>
</html>
