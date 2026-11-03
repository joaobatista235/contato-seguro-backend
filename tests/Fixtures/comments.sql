INSERT INTO admin_user (id, company_id, email, name) VALUES
    (8, 42, 'bob@example.test', 'Bob');

INSERT INTO product_comment (id, product_id, parent_id, admin_user_id, content, created_at, deleted_at) VALUES
    (1, 1, NULL, 7, 'First comment', '2023-01-01 10:00:00', NULL),
    (2, 1, NULL, 8, 'Second comment', '2023-01-02 10:00:00', NULL),
    (3, 1, 1, 8, 'Reply from Bob', '2023-01-02 11:00:00', NULL),
    (4, 1, 3, 7, 'Reply to a reply', '2023-01-02 12:00:00', NULL),
    (5, 6, NULL, 9, 'Foreign comment', '2023-01-01 10:00:00', NULL),
    (6, 2, NULL, 7, 'Other product comment', '2023-01-01 10:00:00', NULL),
    (7, 1, NULL, 7, NULL, '2023-01-03 10:00:00', '2023-01-04 10:00:00'),
    (8, 1, 7, 8, 'Reply kept after removal', '2023-01-03 11:00:00', NULL);

INSERT INTO product_comment_like (comment_id, admin_user_id) VALUES
    (1, 7), (1, 8), (3, 7), (5, 9), (7, 7);
