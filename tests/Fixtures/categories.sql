INSERT INTO company (id, name, active) VALUES
    (42, 'First company', 1),
    (77, 'Second company', 1);

INSERT INTO admin_user (id, company_id, email, name) VALUES
    (7, 42, 'alice@example.test', 'Alice'),
    (9, 77, 'eve@example.test', 'Eve'),
    (10, NULL, 'unassigned@example.test', 'Unassigned admin');

INSERT INTO category (id, company_id, title, active) VALUES
    (1, NULL, 'house', 1),
    (2, 42, 'furniture', 1),
    (3, 77, 'foreign category', 1),
    (4, 42, 'inactive category', 0);

INSERT INTO product (id, company_id, title, price, active) VALUES
    (1, 42, 'Own product', 100, 1),
    (2, 77, 'Foreign product', 100, 1);

INSERT INTO product_category (id, product_id, cat_id) VALUES
    (1, 1, 1),
    (2, 1, 2),
    (3, 2, 1);
