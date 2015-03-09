<?php
$this->startSetup();
$this->run("CREATE TABLE `prizeless_paynow_transactions` (
	`order_id` VARCHAR(50) NULL DEFAULT NULL,
	`paynow_poll_url` VARCHAR(1000) NULL DEFAULT NULL,
	`created_at` DATETIME NULL DEFAULT NULL,
	`updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	UNIQUE INDEX `Index 1` (`order_id`),
	INDEX `Index 2` (`paynow_poll_url`(767))
)
COLLATE='latin1_swedish_ci'
ENGINE=InnoDB");
$this->endSetup();