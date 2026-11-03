INSERT INTO company (id, name, active) VALUES
    (42, 'First company', 1),
    (77, 'Second company', 1);

INSERT INTO admin_user (id, company_id, email, name) VALUES
    (7, 42, 'alice@example.test', 'Alice'),
    (9, 77, 'eve@example.test', 'Eve');

INSERT INTO product (id, company_id, title, price, active, created_at) VALUES
    (1, 42, 'king size bed', 999.99, 1, '2023-12-01 00:00:00'),
    (2, 42, 'uncategorized product', 10, 1, '2023-12-01 00:00:00'),
    (3, 42, 'iphone 8', 399.99, 1, '2023-12-01 00:00:00'),
    (4, 77, 'another company product', 100, 1, '2023-12-01 00:00:00');

INSERT INTO category (id, company_id, title, active) VALUES
    (1, NULL, 'house', 1),
    (2, 42, 'furniture', 1),
    (3, 77, 'another company category', 1),
    (4, 42, 'inactive category', 0),
    (5, NULL, 'phone', 1);

INSERT INTO product_category (id, product_id, cat_id) VALUES
    (11, 1, 2),
    (12, 1, 1),
    (13, 1, 3),
    (14, 1, 4),
    (15, 3, 5),
    (16, 4, 1),
    (17, 1, 2);
