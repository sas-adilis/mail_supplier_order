<?php
$sql = [
    'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'supplier_mail` (
	 `id_supplier` int(11) unsigned NOT NULL,
	 `email` varchar(128) DEFAULT NULL,
	 `active_mail` tinyint(1) NOT NULL DEFAULT \'0\',
    UNIQUE KEY `id_supplier` (`id_supplier`)
    ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;'
];


foreach ($sql as $query) {
    if (!Db::getInstance()->execute($query)) {
        return false;
    }
}