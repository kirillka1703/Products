<?php
// Подключение к базе данных PostgreSQL
try {
    $dsn = "pgsql:host=localhost;port=5432;dbname=postgres"; // Имя вашей базы данных
    $username = "postgres";
    $password = "580085";
    // Создаем подключение
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Определение класса CProducts
    class CProducts {
        private $pdo;
        public function __construct($dbConnection) {
            $this->pdo = $dbConnection;
        }

        // Получение товаров
        public function getProducts($limit) {
            $stmt = $this->pdo->prepare("SELECT *, 
                                          CASE 
                                              WHEN concealment THEN TRUE 
                                              ELSE FALSE 
                                          END as is_hidden 
                                          FROM \"Products\" 
                                          WHERE concealment = FALSE 
                                          ORDER BY date_create DESC 
                                          LIMIT :limit");
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        // Обновление количества товара
        public function updateQuantity($productId, $quantity) {
            $stmt = $this->pdo->prepare("UPDATE \"Products\" SET product_quantity = :quantity WHERE product_id = :productId");
            $stmt->bindValue(':quantity', $quantity, PDO::PARAM_INT);
            $stmt->bindValue(':productId', $productId, PDO::PARAM_INT);
            return $stmt->execute();
        }
        // Скрытие товара
        public function hideProduct($productId) {
            $stmt = $this->pdo->prepare("UPDATE \"Products\" SET concealment = TRUE WHERE product_id = :productId");
            $stmt->bindValue(':productId', $productId, PDO::PARAM_INT);
            return $stmt->execute();
        }
    }
    // Создание экземпляра класса CProducts
    $cProducts = new CProducts($pdo);
    // Обработка запросов
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'hide':
                    if (isset($_POST['product_id'])) {
                        $cProducts->hideProduct($_POST['product_id']);
                    }
                    exit;

                case 'update_quantity': // Обновление количества всех товаров
                    foreach ($_POST['products'] as $product) {
                        $cProducts->updateQuantity($product['id'], $product['quantity']);
                    }
                    exit;

                default:
                    if (isset($_POST['quantity'], $_POST['product_id'])) {
                        $cProducts->updateQuantity($_POST['product_id'], $_POST['quantity']);
                    }
                    exit;
            }
        }
    }
    // Получение товаров для отображения
    $products = $cProducts->getProducts(10);
} catch (PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Товары</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #e2e2e2;
            margin: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }

        .table-container {
            border-radius: 10px;
            overflow: hidden;
            width: 90%; 
            max-width: 800px; 
            background-color: white; 
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1); 
            transition: transform 0.3s;
        }
        .table-container:hover {
            transform: translateY(-2px);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 15px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #4CAF50;
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #45a049;
        }
        .quantity {
            width: 50px;
            text-align: center;
            font-size: 1.2rem;
            font-weight: bold;
        }
        .hidden {
            display: none; 
        }
        #update-button {
            margin: 20px;
            background-color: #007BFF; /* Синий цвет для кнопки обновления */
            transition: background-color 0.3s;
        }
        #update-button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <h1>Товары</h1>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Название товара</th>
                    <th>Цена</th>
                    <th>Артикул</th>
                    <th>Количество</th>
                    <th>Дата создания</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($products as $product): ?>
                <tr id="product-<?php echo $product['product_id']; ?>" class="<?php echo $product['is_hidden'] ? 'hidden' : ''; ?>">
                    <td><?php echo $product['product_id']; ?></td>
                    <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                    <td><?php echo htmlspecialchars($product['product_price']); ?></td>
                    <td><?php echo htmlspecialchars($product['product_article']); ?></td>
                    <td>
                        <button class="decrease" data-id="<?php echo $product['product_id']; ?>">-</button>
                        <span class="quantity" id="quantity-<?php echo $product['product_id']; ?>"><?php echo $product['product_quantity']; ?></span>
                        <button class="increase" data-id="<?php echo $product['product_id']; ?>">+</button>
                    </td>
                    <td><?php echo htmlspecialchars($product['date_create']); ?></td>
                    <td>
                        <button class="hide" data-id="<?php echo $product['product_id']; ?>">Скрыть</button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <!-- Кнопка обновления -->
        <button id="update-button">Обновить</button>
    </div>
    <script>
        // Обработчик нажатия на кнопку "Обновить"
        $(document).on('click', '#update-button', function() {
            const products = [];
            $('tbody tr').each(function() {
                const productId = $(this).find('.increase').data('id');
                const quantity = parseInt($(this).find('.quantity').text());
                products.push({ id: productId, quantity: quantity });
            });
            // Отправляем данные на сервер для обновления количества всех товаров
            $.ajax({
                type: 'POST',
                url: '', // Путь к текущему файлу
                data: {
                    action: 'update_quantity',
                    products: products
                },
                success: function(response) {
                    alert('Количество товаров обновлено успешно!');
                },
                error: function() {
                    alert('Ошибка при обновлении количества товаров');
                }
            });
        });
        $(document).on('click', '.increase, .decrease', function() {
            const productId = $(this).data('id');
            let quantity = parseInt($('#quantity-' + productId).text());

            if ($(this).hasClass('increase')) {
                quantity++;
            } else {
                if (quantity > 0) {
                    quantity--;
                }
            }
            $('#quantity-' + productId).text(quantity);
            // Сохранение измененного количества в базе данных
            $.ajax({
                type: 'POST',
                url: '', // Путь к текущему файлу
                data: {
                    product_id: productId,
                    quantity: quantity
                },
                success: function(response) {
                    console.log('Количество товара обновлено');
                },
                error: function() {
                    alert('Ошибка при обновлении количества товара');
                }
            });
        });
        // Скрытие товара
        $(document).on('click', '.hide', function() {
            const productId = $(this).data('id');

            if (confirm('Вы уверены, что хотите скрыть этот товар?')) {
                $.post('', { action: 'hide', product_id: productId })
                    .done(function() {
                        $('#product-' + productId).fadeOut(); // Используем fadeOut для плавного скрытия
                    })
                    .fail(function() {
                        alert('Произошла ошибка при скрытии товара.'); // Сообщение об ошибке
                    });
            }
        });
    </script>
</body>
</html>