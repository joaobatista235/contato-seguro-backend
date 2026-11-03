INSERT INTO company (id, name, active) VALUES
    (42, '<Acme>', 1),
    (77, 'Another company', 1);

INSERT INTO admin_user (id, company_id, email, name) VALUES
    (7, 42, 'alice@example.test', 'Alice <admin>'),
    (8, 42, 'bob@example.test', 'Bob'),
    (9, 77, 'eve@example.test', 'Eve');

INSERT INTO product (id, company_id, title, price, active, created_at) VALUES
    (1, 42, '<script>unsafe</script>', 999.99, 1, '2023-12-01'),
    (2, 42, 'Product without logs', 10, 1, '2023-12-01'),
    (3, 77, 'Foreign product', 100, 1, '2023-12-01');

INSERT INTO category (id, company_id, title, active) VALUES
    (1, NULL, 'clothing', 1),
    (2, NULL, 'phone', 1),
    (3, NULL, 'computer', 1),
    (4, NULL, 'house', 1),
    (5, 42, 'furniture', 1),
    (6, 77, 'foreign category', 1);

INSERT INTO product_category (id, product_id, cat_id) VALUES
    (1, 1, 4),
    (2, 1, 5);

INSERT INTO product_log (id, product_id, admin_user_id, action, timestamp) VALUES
    (20, 1, 7, 'update', '2023-12-22 18:12:10'),
    (10, 1, 8, 'create', '2023-12-20 21:32:22'),
    (21, 1, 99, 'delete', '2023-12-22 18:12:10');
