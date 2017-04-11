-- varchar(0) is dangerous, because drop the data
ALTER TABLE `_escuela_chapter`
MODIFY COLUMN `content`  longtext CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL AFTER `title`;