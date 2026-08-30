CREATE TABLE orders
(
	id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
	shopping_location_id INTEGER,
	ordered_date DATE NOT NULL,
	arrive_date DATE,
	status TEXT NOT NULL DEFAULT 'pending' CHECK(status IN ('pending', 'ordered', 'delivered', 'cancelled')),
	note TEXT,
	row_created_timestamp DATETIME DEFAULT (datetime('now', 'localtime'))
);

CREATE TABLE order_items
(
	id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
	order_id INTEGER NOT NULL,
	product_id INTEGER NOT NULL,
	amount DECIMAL(15, 2) NOT NULL,
	qu_id INTEGER,
	price DECIMAL(15, 2),
	note TEXT,
	row_created_timestamp DATETIME DEFAULT (datetime('now', 'localtime'))
);

CREATE INDEX idx_orders_ordered_date ON orders(ordered_date);
CREATE INDEX idx_order_items_order_id ON order_items(order_id);
CREATE INDEX idx_order_items_product_id ON order_items(product_id);

CREATE VIEW orders_current
AS
SELECT
	o.id,
	o.shopping_location_id,
	sl.name AS shopping_location_name,
	o.ordered_date,
	o.arrive_date,
	o.status,
	o.note,
	(SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS item_count,
	(SELECT COALESCE(SUM(oi.amount * oi.price), 0) FROM order_items oi WHERE oi.order_id = o.id) AS total_price
FROM orders o
LEFT JOIN shopping_locations sl ON sl.id = o.shopping_location_id;

-- Permissions
INSERT INTO permission_hierarchy
	(name, parent)
VALUES
	('ORDERS', (SELECT id FROM permission_hierarchy WHERE name = 'ADMIN'));

INSERT INTO permission_hierarchy
	(name, parent)
VALUES
	('ORDERS_TRACK', (SELECT id FROM permission_hierarchy WHERE name = 'ORDERS')),
	('ORDERS_EDIT', (SELECT id FROM permission_hierarchy WHERE name = 'ORDERS')),
	('ORDERS_DELETE', (SELECT id FROM permission_hierarchy WHERE name = 'ORDERS'));
