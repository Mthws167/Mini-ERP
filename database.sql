CREATE DATABASE IF NOT EXISTS OnlineStore;
USE OnlineStore;

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL COMMENT 'Nome do produto',
    price DECIMAL(10, 2) NOT NULL COMMENT 'Preço unitário do produto',
    INDEX idx_name (name)
) COMMENT 'Armazena informações dos produtos disponíveis para venda';

CREATE TABLE stock (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL COMMENT 'ID do produto associado',
    quantity INT NOT NULL COMMENT 'Quantidade disponível em estoque',
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product_id (product_id)
) COMMENT 'Gerencia o estoque de produtos';

CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(255) NOT NULL COMMENT 'Nome do cliente',
    customer_email VARCHAR(255) NOT NULL COMMENT 'E-mail do cliente',
    customer_address VARCHAR(255) NOT NULL COMMENT 'Endereço de entrega',
    total DECIMAL(10, 2) NOT NULL COMMENT 'Valor total do pedido',
    status VARCHAR(50) NOT NULL DEFAULT 'pending' COMMENT 'Status do pedido (pending, shipped, canceled)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Data de criação do pedido',
    INDEX idx_customer_email (customer_email)
) COMMENT 'Armazena os pedidos realizados pelos clientes';

CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL COMMENT 'ID do pedido associado',
    product_id INT NOT NULL COMMENT 'ID do produto',
    quantity INT NOT NULL COMMENT 'Quantidade comprada',
    price DECIMAL(10, 2) NOT NULL COMMENT 'Preço unitário no momento da compra',
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_order_id (order_id)
) COMMENT 'Detalha os itens incluídos em cada pedido';

CREATE TABLE coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE COMMENT 'Código do cupom',
    discount_type ENUM('percentage', 'fixed') NOT NULL COMMENT 'Tipo de desconto (porcentagem ou fixo)',
    discount_value DECIMAL(10, 2) NOT NULL COMMENT 'Valor do desconto',
    min_order_value DECIMAL(10, 2) COMMENT 'Valor mínimo do pedido para aplicar o cupom',
    valid_from DATE COMMENT 'Data de início da validade',
    valid_until DATE COMMENT 'Data de término da validade',
    INDEX idx_code (code)
) COMMENT 'Gerencia os cupons de desconto disponíveis';