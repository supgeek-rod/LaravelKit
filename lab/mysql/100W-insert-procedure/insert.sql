# 业务数据库：
CREATE TABLE IF NOT EXISTS `invite_code`
(
    `invite_id`    VARCHAR(64) NOT NULL COMMENT '邀请码Id',
    `invite_code`  VARCHAR(16) NOT NULL COMMENT '邀请码',
    `owner_id`     VARCHAR(64) NOT NULL COMMENT '账号Id',
    `expire_time`  DATETIME    NOT NULL COMMENT '有效时间',
    `is_permanent` TINYINT(1)  NOT NULL DEFAULT 0 COMMENT '是否永久, 0 否, 1 是',
    `is_expired`   BIGINT      NOT NULL DEFAULT 0 COMMENT '是否过期',
    `create_time`  DATETIME    NOT NULL COMMENT '创建时间',
    `modify_time`  DATETIME    NOT NULL COMMENT '更新时间',
    PRIMARY KEY (`invite_id`),
    UNIQUE KEY `uni_invite_code` (`invite_code`, `is_expired`)
) ENGINE = InnoDB
  DEFAULT CHARSET utf8mb4
  COLLATE = utf8mb4_unicode_ci COMMENT '邀请码';

# 存储过程: 向业务表插入 n 条数据
DELIMITER $$
CREATE PROCEDURE `insertBatchMemory`(IN n int)
BEGIN
    DECLARE i INT DEFAULT 1;
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION, SQLWARNING, NOT FOUND SELECT 'error!!!';
    WHILE (i <= n)
        DO
            INSERT INTO invite_code (invite_id, invite_code, owner_id, expire_time,
                                     is_permanent, is_expired, create_time, modify_time)
            VALUES (CONCAT('i_', MD5(UUID())), SUBSTRING(MD5(UUID()), 4, 8),
                    CONCAT('u_', MD5(UUID())), NOW(), 0, 0, NOW(), NOW());
            SET i = i + 1;
        END WHILE;
END $$
DELIMITER ;

# 存储过程： 调用 n 次，每批次 count 个
DELIMITER $$
CREATE PROCEDURE `insertInviteCodeBatch`(IN n int, IN count int)
BEGIN
    DECLARE i INT DEFAULT 1;
    WHILE (i <= n)
        DO
            CALL insertBatchMemory(count);
            SET i = i + 1;
        END WHILE;
END $$
DELIMITER ;


# 单次调用
CALL insertBatchMemory(10000);


# 批量调用
CALL insertInviteCodeBatch(100, 10000);
