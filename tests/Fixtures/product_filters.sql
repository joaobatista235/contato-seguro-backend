INSERT INTO company (id, name, active) VALUES
    (42, 'First company', 1),
    (77, 'Second company', 1);

INSERT INTO admin_user (id, company_id, email, name) VALUES
    (7, 42, 'alice@example.test', 'Alice'),
    (9, 77, 'eve@example.test', 'Eve');

INSERT INTO category (id, company_id, title, active) VALUES
    (1, NULL, 'house', 1),
    (2, 42, 'furniture', 1),
    (3, 77, 'foreign category', 1),
    (4, 42, 'inactive category', 0);

INSERT INTO product (id, company_id, title, price, active, created_at) VALUES
    (1, 42, 'Old active product', 10, 1, '2023-01-01 10:00:00'),
    (2, 42, 'New inactive product', 20, 0, '2023-01-03 10:00:00'),
    (3, 42, 'Middle active product', 30, 1, '2023-01-02 10:00:00'),
    (4, 42, 'Same date product', 40, 1, '2023-01-02 10:00:00'),
    (5, 42, 'Uncategorized inactive product', 50, 0, '2023-01-04 10:00:00'),
    (6, 77, 'Foreign active product', 60, 1, '2023-01-05 10:00:00');

INSERT INTO product_category (id, product_id, cat_id) VALUES
    (1, 1, 1), (2, 1, 2), (3, 1, 2),
    (4, 2, 1), (5, 3, 2), (6, 4, 3),
    (7, 4, 4), (8, 6, 1), (9, 6, 3);
