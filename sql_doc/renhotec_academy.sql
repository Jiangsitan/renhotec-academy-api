/*
 Navicat Premium Dump SQL

 Source Server         : localhost
 Source Server Type    : MySQL
 Source Server Version : 90600 (9.6.0)
 Source Host           : localhost:3306
 Source Schema         : renhotec_academy

 Target Server Type    : MySQL
 Target Server Version : 90600 (9.6.0)
 File Encoding         : 65001

 Date: 15/06/2026 17:40:53
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for attachments
-- ----------------------------
DROP TABLE IF EXISTS `attachments`;
CREATE TABLE `attachments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `course_id` bigint unsigned NOT NULL COMMENT '所属课程ID',
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '原始文件名',
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '存储路径',
  `file_size` bigint unsigned NOT NULL DEFAULT '0' COMMENT '文件大小(字节)',
  `mime_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '文件MIME类型',
  `download_count` int unsigned NOT NULL DEFAULT '0' COMMENT '下载次数',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `attachments_course_id_index` (`course_id`),
  CONSTRAINT `attachments_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='课程附件表：区分课程附件和工具附件';

-- ----------------------------
-- Records of attachments
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for audit_logs
-- ----------------------------
DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `user_id` bigint unsigned NOT NULL COMMENT '操作用户ID',
  `action_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '操作类型',
  `target_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '目标类型',
  `target_id` bigint unsigned NOT NULL COMMENT '目标ID',
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '客户端IP地址',
  `user_agent` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '客户端浏览器信息',
  `extra_data` json DEFAULT NULL COMMENT '额外数据（JSON格式）',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `audit_logs_user_id_index` (`user_id`),
  KEY `audit_logs_action_type_target_type_index` (`action_type`,`target_type`),
  KEY `audit_logs_created_at_index` (`created_at`),
  CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=78 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='审计日志表：记录系统操作日志';

-- ----------------------------
-- Records of audit_logs
-- ----------------------------
BEGIN;
INSERT INTO `audit_logs` (`id`, `user_id`, `action_type`, `target_type`, `target_id`, `ip_address`, `user_agent`, `extra_data`, `created_at`) VALUES (36, 4, 'create_course', 'course', 9, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '{\"title\": \"《认识仁昊伟业》\", \"content_source\": \"local\"}', '2026-06-09 07:11:48');
INSERT INTO `audit_logs` (`id`, `user_id`, `action_type`, `target_type`, `target_id`, `ip_address`, `user_agent`, `extra_data`, `created_at`) VALUES (37, 4, 'update_course', 'course', 9, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-06-09 07:11:53');
INSERT INTO `audit_logs` (`id`, `user_id`, `action_type`, `target_type`, `target_id`, `ip_address`, `user_agent`, `extra_data`, `created_at`) VALUES (38, 4, 'create_course', 'course', 10, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '{\"title\": \"《人事行政管理制度》\", \"content_source\": \"local\"}', '2026-06-09 07:12:33');
INSERT INTO `audit_logs` (`id`, `user_id`, `action_type`, `target_type`, `target_id`, `ip_address`, `user_agent`, `extra_data`, `created_at`) VALUES (39, 4, 'update_course', 'course', 10, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-06-09 07:12:38');
INSERT INTO `audit_logs` (`id`, `user_id`, `action_type`, `target_type`, `target_id`, `ip_address`, `user_agent`, `extra_data`, `created_at`) VALUES (40, 4, 'create_course', 'course', 11, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '{\"title\": \"2026_06_09_连接器产品导论（一）\", \"content_source\": \"local\"}', '2026-06-09 08:10:44');
INSERT INTO `audit_logs` (`id`, `user_id`, `action_type`, `target_type`, `target_id`, `ip_address`, `user_agent`, `extra_data`, `created_at`) VALUES (41, 4, 'update_course', 'course', 11, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-06-09 08:10:48');
INSERT INTO `audit_logs` (`id`, `user_id`, `action_type`, `target_type`, `target_id`, `ip_address`, `user_agent`, `extra_data`, `created_at`) VALUES (42, 4, 'create_course', 'course', 12, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '{\"title\": \"仁昊连接器产品导论（一）\", \"content_source\": \"local\"}', '2026-06-09 08:11:48');
INSERT INTO `audit_logs` (`id`, `user_id`, `action_type`, `target_type`, `target_id`, `ip_address`, `user_agent`, `extra_data`, `created_at`) VALUES (43, 4, 'update_course', 'course', 12, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-06-09 08:11:53');
INSERT INTO `audit_logs` (`id`, `user_id`, `action_type`, `target_type`, `target_id`, `ip_address`, `user_agent`, `extra_data`, `created_at`) VALUES (44, 4, 'create_course', 'course', 13, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '{\"title\": \"2026_06_09_连接器产品导论（二）\", \"content_source\": \"local\"}', '2026-06-09 08:12:54');
INSERT INTO `audit_logs` (`id`, `user_id`, `action_type`, `target_type`, `target_id`, `ip_address`, `user_agent`, `extra_data`, `created_at`) VALUES (45, 4, 'update_course', 'course', 13, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-06-09 08:12:56');
INSERT INTO `audit_logs` (`id`, `user_id`, `action_type`, `target_type`, `target_id`, `ip_address`, `user_agent`, `extra_data`, `created_at`) VALUES (46, 4, 'create_course', 'course', 14, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '{\"title\": \"仁昊连接器产品导论（二）\", \"content_source\": \"local\"}', '2026-06-09 08:13:27');
INSERT INTO `audit_logs` (`id`, `user_id`, `action_type`, `target_type`, `target_id`, `ip_address`, `user_agent`, `extra_data`, `created_at`) VALUES (47, 4, 'update_course', 'course', 14, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-06-09 08:13:28');
INSERT INTO `audit_logs` (`id`, `user_id`, `action_type`, `target_type`, `target_id`, `ip_address`, `user_agent`, `extra_data`, `created_at`) VALUES (48, 4, 'create_course', 'course', 15, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '{\"title\": \"2026_06_09_连接器产品导论（三）\", \"content_source\": \"local\"}', '2026-06-09 08:24:11');
INSERT INTO `audit_logs` (`id`, `user_id`, `action_type`, `target_type`, `target_id`, `ip_address`, `user_agent`, `extra_data`, `created_at`) VALUES (49, 4, 'update_course', 'course', 15, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-06-09 08:24:14');
INSERT INTO `audit_logs` (`id`, `user_id`, `action_type`, `target_type`, `target_id`, `ip_address`, `user_agent`, `extra_data`, `created_at`) VALUES (50, 4, 'create_course', 'course', 16, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '{\"title\": \"仁昊连接器产品导论（三）\", \"content_source\": \"local\"}', '2026-06-09 08:25:11');
INSERT INTO `audit_logs` (`id`, `user_id`, `action_type`, `target_type`, `target_id`, `ip_address`, `user_agent`, `extra_data`, `created_at`) VALUES (51, 4, 'update_course', 'course', 16, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-06-09 08:25:56');
INSERT INTO `audit_logs` (`id`, `user_id`, `action_type`, `target_type`, `target_id`, `ip_address`, `user_agent`, `extra_data`, `created_at`) VALUES (52, 4, 'create_exam', 'exam', 5, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-06-09 09:17:27');
INSERT INTO `audit_logs` (`id`, `user_id`, `action_type`, `target_type`, `target_id`, `ip_address`, `user_agent`, `extra_data`, `created_at`) VALUES (53, 8, 'submit_exam', 'exam', 5, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-06-09 11:22:31');
INSERT INTO `audit_logs` (`id`, `user_id`, `action_type`, `target_type`, `target_id`, `ip_address`, `user_agent`, `extra_data`, `created_at`) VALUES (54, 4, 'create_user', 'user', 12, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '{\"role\": \"admin\", \"employee_no\": \"Renhotec001\"}', '2026-06-10 00:31:19');
INSERT INTO `audit_logs` (`id`, `user_id`, `action_type`, `target_type`, `target_id`, `ip_address`, `user_agent`, `extra_data`, `created_at`) VALUES (55, 4, 'update_course', 'course', 16, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-06-10 01:19:31');
INSERT INTO `audit_logs` (`id`, `user_id`, `action_type`, `target_type`, `target_id`, `ip_address`, `user_agent`, `extra_data`, `created_at`) VALUES (56, 4, 'update_course', 'course', 14, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-06-10 01:19:39');
INSERT INTO `audit_logs` (`id`, `user_id`, `action_type`, `target_type`, `target_id`, `ip_address`, `user_agent`, `extra_data`, `created_at`) VALUES (57, 4, 'update_course', 'course', 12, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-06-10 01:19:49');
INSERT INTO `audit_logs` (`id`, `user_id`, `action_type`, `target_type`, `target_id`, `ip_address`, `user_agent`, `extra_data`, `created_at`) VALUES (58, 4, 'create_course', 'course', 17, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '{\"title\": \"测试\", \"content_source\": \"online\"}', '2026-06-10 01:34:11');
INSERT INTO `audit_logs` (`id`, `user_id`, `action_type`, `target_type`, `target_id`, `ip_address`, `user_agent`, `extra_data`, `created_at`) VALUES (59, 12, 'update_course', 'course', 15, '192.168.1.114', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', NULL, '2026-06-10 02:41:19');
INSERT INTO `audit_logs` (`id`, `user_id`, `action_type`, `target_type`, `target_id`, `ip_address`, `user_agent`, `extra_data`, `created_at`) VALUES (60, 12, 'submit_exam', 'exam', 5, '192.168.1.168', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-06-10 06:10:18');
INSERT INTO `audit_logs` (`id`, `user_id`, `action_type`, `target_type`, `target_id`, `ip_address`, `user_agent`, `extra_data`, `created_at`) VALUES (61, 11, 'submit_exam', 'exam', 5, '192.168.1.248', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-06-10 10:11:49');
INSERT INTO `audit_logs` (`id`, `user_id`, `action_type`, `target_type`, `target_id`, `ip_address`, `user_agent`, `extra_data`, `created_at`) VALUES (62, 8, 'submit_exam', 'exam', 5, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-06-10 15:58:05');
INSERT INTO `audit_logs` (`id`, `user_id`, `action_type`, `target_type`, `target_id`, `ip_address`, `user_agent`, `extra_data`, `created_at`) VALUES (63, 4, 'update_exam', 'exam', 5, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-06-10 18:56:32');
INSERT INTO `audit_logs` (`id`, `user_id`, `action_type`, `target_type`, `target_id`, `ip_address`, `user_agent`, `extra_data`, `created_at`) VALUES (64, 4, 'update_exam', 'exam', 5, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-06-10 22:39:01');
INSERT INTO `audit_logs` (`id`, `user_id`, `action_type`, `target_type`, `target_id`, `ip_address`, `user_agent`, `extra_data`, `created_at`) VALUES (65, 11, 'submit_exam', 'exam', 5, '192.168.1.114', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', NULL, '2026-06-11 00:33:18');
INSERT INTO `audit_logs` (`id`, `user_id`, `action_type`, `target_type`, `target_id`, `ip_address`, `user_agent`, `extra_data`, `created_at`) VALUES (66, 11, 'submit_exam', 'exam', 5, '192.168.1.114', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', NULL, '2026-06-11 00:55:25');
INSERT INTO `audit_logs` (`id`, `user_id`, `action_type`, `target_type`, `target_id`, `ip_address`, `user_agent`, `extra_data`, `created_at`) VALUES (67, 4, 'update_exam', 'exam', 5, '192.168.1.248', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-06-11 00:56:41');
INSERT INTO `audit_logs` (`id`, `user_id`, `action_type`, `target_type`, `target_id`, `ip_address`, `user_agent`, `extra_data`, `created_at`) VALUES (68, 4, 'create_user', 'user', 13, '192.168.1.248', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '{\"role\": \"student\", \"employee_no\": \"test032\"}', '2026-06-11 01:40:08');
INSERT INTO `audit_logs` (`id`, `user_id`, `action_type`, `target_type`, `target_id`, `ip_address`, `user_agent`, `extra_data`, `created_at`) VALUES (69, 12, 'update_course', 'course', 16, '192.168.1.114', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', NULL, '2026-06-11 02:08:46');
INSERT INTO `audit_logs` (`id`, `user_id`, `action_type`, `target_type`, `target_id`, `ip_address`, `user_agent`, `extra_data`, `created_at`) VALUES (70, 12, 'update_course', 'course', 15, '192.168.1.114', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', NULL, '2026-06-11 02:08:58');
INSERT INTO `audit_logs` (`id`, `user_id`, `action_type`, `target_type`, `target_id`, `ip_address`, `user_agent`, `extra_data`, `created_at`) VALUES (71, 12, 'update_course', 'course', 14, '192.168.1.114', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', NULL, '2026-06-11 02:09:12');
INSERT INTO `audit_logs` (`id`, `user_id`, `action_type`, `target_type`, `target_id`, `ip_address`, `user_agent`, `extra_data`, `created_at`) VALUES (72, 12, 'update_course', 'course', 15, '192.168.1.114', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', NULL, '2026-06-11 02:09:23');
INSERT INTO `audit_logs` (`id`, `user_id`, `action_type`, `target_type`, `target_id`, `ip_address`, `user_agent`, `extra_data`, `created_at`) VALUES (73, 12, 'update_course', 'course', 13, '192.168.1.114', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', NULL, '2026-06-11 02:09:41');
INSERT INTO `audit_logs` (`id`, `user_id`, `action_type`, `target_type`, `target_id`, `ip_address`, `user_agent`, `extra_data`, `created_at`) VALUES (74, 12, 'update_course', 'course', 15, '192.168.1.114', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', NULL, '2026-06-11 02:09:51');
INSERT INTO `audit_logs` (`id`, `user_id`, `action_type`, `target_type`, `target_id`, `ip_address`, `user_agent`, `extra_data`, `created_at`) VALUES (75, 12, 'update_course', 'course', 12, '192.168.1.114', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', NULL, '2026-06-11 02:10:01');
INSERT INTO `audit_logs` (`id`, `user_id`, `action_type`, `target_type`, `target_id`, `ip_address`, `user_agent`, `extra_data`, `created_at`) VALUES (76, 12, 'update_course', 'course', 11, '192.168.1.114', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', NULL, '2026-06-11 02:10:12');
INSERT INTO `audit_logs` (`id`, `user_id`, `action_type`, `target_type`, `target_id`, `ip_address`, `user_agent`, `extra_data`, `created_at`) VALUES (77, 4, 'submit_exam', 'exam', 5, '192.168.1.248', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-06-11 02:14:57');
COMMIT;

-- ----------------------------
-- Table structure for cache
-- ----------------------------
DROP TABLE IF EXISTS `cache`;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '缓存键',
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '缓存值',
  `expiration` bigint NOT NULL COMMENT '过期时间',
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Laravel 缓存表';

-- ----------------------------
-- Records of cache
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for cache_locks
-- ----------------------------
DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '锁键',
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '锁所有者',
  `expiration` int NOT NULL COMMENT '过期时间',
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Laravel 缓存锁表';

-- ----------------------------
-- Records of cache_locks
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for categories
-- ----------------------------
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '分类名称',
  `parent_id` bigint unsigned DEFAULT NULL COMMENT '父分类ID，NULL表示一级分类',
  `sort_order` int NOT NULL DEFAULT '0' COMMENT '排序权重',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='课程分类表：支持两级分类结构';

-- ----------------------------
-- Records of categories
-- ----------------------------
BEGIN;
INSERT INTO `categories` (`id`, `name`, `parent_id`, `sort_order`, `created_at`, `updated_at`) VALUES (16, '通识', NULL, 1, '2026-06-09 05:46:12', '2026-06-10 02:08:43');
INSERT INTO `categories` (`id`, `name`, `parent_id`, `sort_order`, `created_at`, `updated_at`) VALUES (18, '通用能力', NULL, 6, '2026-06-09 05:46:29', '2026-06-10 02:09:30');
INSERT INTO `categories` (`id`, `name`, `parent_id`, `sort_order`, `created_at`, `updated_at`) VALUES (19, '岗位基础', NULL, 3, '2026-06-09 05:46:40', '2026-06-10 02:09:05');
INSERT INTO `categories` (`id`, `name`, `parent_id`, `sort_order`, `created_at`, `updated_at`) VALUES (20, '流程与工具', NULL, 4, '2026-06-09 05:46:51', '2026-06-10 02:09:16');
INSERT INTO `categories` (`id`, `name`, `parent_id`, `sort_order`, `created_at`, `updated_at`) VALUES (21, '岗位技能', NULL, 5, '2026-06-09 05:47:01', '2026-06-10 02:09:25');
INSERT INTO `categories` (`id`, `name`, `parent_id`, `sort_order`, `created_at`, `updated_at`) VALUES (22, '产品知识', NULL, 2, '2026-06-09 05:47:46', '2026-06-10 02:08:53');
COMMIT;

-- ----------------------------
-- Table structure for comment_likes
-- ----------------------------
DROP TABLE IF EXISTS `comment_likes`;
CREATE TABLE `comment_likes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `comment_id` bigint unsigned NOT NULL COMMENT '评论ID',
  `user_id` bigint unsigned NOT NULL COMMENT '点赞用户ID',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `comment_likes_comment_id_user_id_unique` (`comment_id`,`user_id`),
  KEY `comment_likes_user_id_foreign` (`user_id`),
  CONSTRAINT `comment_likes_comment_id_foreign` FOREIGN KEY (`comment_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `comment_likes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='评论点赞表';

-- ----------------------------
-- Records of comment_likes
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for comments
-- ----------------------------
DROP TABLE IF EXISTS `comments`;
CREATE TABLE `comments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `series_id` bigint unsigned NOT NULL COMMENT '所属系列ID',
  `user_id` bigint unsigned NOT NULL COMMENT '评论用户ID',
  `parent_id` bigint unsigned DEFAULT NULL COMMENT '父评论ID（用于回复）',
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '评论内容',
  `likes_count` int unsigned NOT NULL COMMENT '点赞数',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `comments_series_id_index` (`series_id`),
  KEY `comments_user_id_index` (`user_id`),
  KEY `comments_parent_id_index` (`parent_id`),
  KEY `comments_series_id_created_at_index` (`series_id`,`created_at`),
  CONSTRAINT `comments_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `comments_series_id_foreign` FOREIGN KEY (`series_id`) REFERENCES `series` (`id`) ON DELETE CASCADE,
  CONSTRAINT `comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='课程评论表';

-- ----------------------------
-- Records of comments
-- ----------------------------
BEGIN;
INSERT INTO `comments` (`id`, `series_id`, `user_id`, `parent_id`, `content`, `likes_count`, `created_at`, `updated_at`) VALUES (1, 24, 11, NULL, '@张导师 导师测试课程', 0, '2026-06-09 15:04:15', '2026-06-09 15:04:15');
COMMIT;

-- ----------------------------
-- Table structure for courses
-- ----------------------------
DROP TABLE IF EXISTS `courses`;
CREATE TABLE `courses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '课程标题',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '课程简介',
  `category_id` bigint unsigned NOT NULL COMMENT '所属分类ID',
  `series_id` bigint unsigned DEFAULT NULL COMMENT '所属系列ID',
  `mentor_id` bigint unsigned DEFAULT NULL COMMENT '绑定的导师',
  `type` enum('document','video') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '课程类型',
  `content_url` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '视频URL或文档路径',
  `content_source` enum('online','local') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'online' COMMENT '内容来源',
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '原始文件名',
  `file_size` bigint unsigned DEFAULT NULL COMMENT '文件大小(字节)',
  `cover_image` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '封面图URL',
  `min_read_time` int unsigned NOT NULL DEFAULT '0' COMMENT '文档最低阅读秒数',
  `duration` int unsigned NOT NULL DEFAULT '0' COMMENT '视频总时长秒数',
  `status` enum('draft','published','archived') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft' COMMENT '状态：draft=草稿, published=已发布, archived=已归档',
  `sort_order` int NOT NULL DEFAULT '0' COMMENT '排序权重',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `courses_category_id_index` (`category_id`),
  KEY `courses_type_index` (`type`),
  KEY `courses_status_index` (`status`),
  KEY `courses_category_id_status_index` (`category_id`,`status`),
  KEY `courses_series_id_index` (`series_id`),
  CONSTRAINT `courses_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `courses_series_id_foreign` FOREIGN KEY (`series_id`) REFERENCES `series` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='课程表：支持视频和文档两种类型';

-- ----------------------------
-- Records of courses
-- ----------------------------
BEGIN;
INSERT INTO `courses` (`id`, `title`, `description`, `category_id`, `series_id`, `mentor_id`, `type`, `content_url`, `content_source`, `file_name`, `file_size`, `cover_image`, `min_read_time`, `duration`, `status`, `sort_order`, `created_at`, `updated_at`) VALUES (9, '《认识仁昊伟业》', NULL, 16, 3, NULL, 'document', 'academy/dev/documents/courses/2026/06/1780989068_IMYBwLYrT8.pdf', 'local', '1-认识仁昊伟业20260304.pdf', 9878171, NULL, 30, 0, 'published', 0, '2026-06-09 07:11:48', '2026-06-10 13:46:42');
INSERT INTO `courses` (`id`, `title`, `description`, `category_id`, `series_id`, `mentor_id`, `type`, `content_url`, `content_source`, `file_name`, `file_size`, `cover_image`, `min_read_time`, `duration`, `status`, `sort_order`, `created_at`, `updated_at`) VALUES (10, '《人事行政管理制度》', NULL, 16, 3, NULL, 'document', 'academy/dev/documents/courses/2026/06/1780989146_diJZGxhZzz.pdf', 'local', '2-人事行政管理制度20260311.pdf', 1760841, NULL, 30, 0, 'published', 0, '2026-06-09 07:12:33', '2026-06-10 13:46:42');
INSERT INTO `courses` (`id`, `title`, `description`, `category_id`, `series_id`, `mentor_id`, `type`, `content_url`, `content_source`, `file_name`, `file_size`, `cover_image`, `min_read_time`, `duration`, `status`, `sort_order`, `created_at`, `updated_at`) VALUES (11, '连接器产品导论（一）MP4', NULL, 16, 24, NULL, 'video', 'academy/dev/videos/courses/2026/06/1780992638_DVZD8TN3Q0.mp4', 'local', '连接器产品导论（一）.mp4', 24790916, NULL, 0, 728, 'published', 0, '2026-06-09 08:10:44', '2026-06-11 02:10:12');
INSERT INTO `courses` (`id`, `title`, `description`, `category_id`, `series_id`, `mentor_id`, `type`, `content_url`, `content_source`, `file_name`, `file_size`, `cover_image`, `min_read_time`, `duration`, `status`, `sort_order`, `created_at`, `updated_at`) VALUES (12, '连接器产品导论（一）PPT', NULL, 16, 24, NULL, 'document', 'academy/dev/documents/courses/2026/06/1780992698_d0R9bzAekE.pptx', 'local', '仁昊连接器产品导论（一）.pptx', 4792722, NULL, 30, 0, 'published', 0, '2026-06-09 08:11:48', '2026-06-11 02:10:01');
INSERT INTO `courses` (`id`, `title`, `description`, `category_id`, `series_id`, `mentor_id`, `type`, `content_url`, `content_source`, `file_name`, `file_size`, `cover_image`, `min_read_time`, `duration`, `status`, `sort_order`, `created_at`, `updated_at`) VALUES (13, '连接器产品导论（二）MP4', NULL, 16, 24, NULL, 'video', 'academy/dev/videos/courses/2026/06/1780992770_QiqMYJZ6nn.mp4', 'local', '连接器产品导论（二）.mp4', 24834893, NULL, 0, 702, 'published', 0, '2026-06-09 08:12:54', '2026-06-11 02:09:41');
INSERT INTO `courses` (`id`, `title`, `description`, `category_id`, `series_id`, `mentor_id`, `type`, `content_url`, `content_source`, `file_name`, `file_size`, `cover_image`, `min_read_time`, `duration`, `status`, `sort_order`, `created_at`, `updated_at`) VALUES (14, '连接器产品导论（二）PPT', NULL, 16, 24, NULL, 'document', 'academy/dev/documents/courses/2026/06/1780992801_w6oTtw0CNP.pptx', 'local', '仁昊连接器产品导论（二）.pptx', 11474699, NULL, 30, 0, 'published', 0, '2026-06-09 08:13:27', '2026-06-11 02:09:12');
INSERT INTO `courses` (`id`, `title`, `description`, `category_id`, `series_id`, `mentor_id`, `type`, `content_url`, `content_source`, `file_name`, `file_size`, `cover_image`, `min_read_time`, `duration`, `status`, `sort_order`, `created_at`, `updated_at`) VALUES (15, '连接器产品导论（三）MP4', NULL, 16, 24, NULL, 'video', 'academy/dev/videos/courses/2026/06/1780993448_uwouEnlJIs.mp4', 'local', '连接器产品导论（三）.mp4', 70656134, NULL, 0, 1220, 'published', 0, '2026-06-09 08:24:11', '2026-06-11 02:09:51');
INSERT INTO `courses` (`id`, `title`, `description`, `category_id`, `series_id`, `mentor_id`, `type`, `content_url`, `content_source`, `file_name`, `file_size`, `cover_image`, `min_read_time`, `duration`, `status`, `sort_order`, `created_at`, `updated_at`) VALUES (16, '连接器产品导论（三）PPT', NULL, 16, 24, NULL, 'document', 'academy/dev/documents/courses/2026/06/1780993475_U3V1AiceIa.pptx', 'local', '仁昊连接器产品导论（三）.pptx', 3400171, NULL, 30, 0, 'published', 0, '2026-06-09 08:25:11', '2026-06-11 02:08:46');
INSERT INTO `courses` (`id`, `title`, `description`, `category_id`, `series_id`, `mentor_id`, `type`, `content_url`, `content_source`, `file_name`, `file_size`, `cover_image`, `min_read_time`, `duration`, `status`, `sort_order`, `created_at`, `updated_at`) VALUES (17, '测试', NULL, 16, 24, NULL, 'video', 'bilibili.com/video/BV1mH5U6pEDo/?spm_id_from=333.1007.tianma.1-2-2.click', 'online', NULL, 0, NULL, 120, 0, 'draft', 0, '2026-06-10 01:34:11', '2026-06-10 02:41:01');
COMMIT;

-- ----------------------------
-- Table structure for exam_cheats
-- ----------------------------
DROP TABLE IF EXISTS `exam_cheats`;
CREATE TABLE `exam_cheats` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `user_id` bigint unsigned NOT NULL COMMENT '学员用户ID',
  `exam_id` bigint unsigned NOT NULL COMMENT '考试ID',
  `exam_record_id` bigint unsigned DEFAULT NULL COMMENT '考试记录ID',
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '作弊类型：leave_page=离开页面, blur=窗口失焦, exit_fullscreen=退出全屏',
  `detail` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '详情',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `exam_cheats_exam_id_foreign` (`exam_id`),
  KEY `exam_cheats_exam_record_id_foreign` (`exam_record_id`),
  KEY `exam_cheats_user_id_exam_id_index` (`user_id`,`exam_id`),
  CONSTRAINT `exam_cheats_exam_id_foreign` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `exam_cheats_exam_record_id_foreign` FOREIGN KEY (`exam_record_id`) REFERENCES `exam_records` (`id`) ON DELETE SET NULL,
  CONSTRAINT `exam_cheats_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=108 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='考试作弊记录表';

-- ----------------------------
-- Records of exam_cheats
-- ----------------------------
BEGIN;
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (1, 11, 5, NULL, 'exit_fullscreen', '检测到退出全屏模式', '2026-06-10 09:47:33', '2026-06-10 09:47:33');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (2, 11, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-10 09:47:36', '2026-06-10 09:47:36');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (3, 11, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-10 09:47:56', '2026-06-10 09:47:56');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (4, 11, 5, NULL, 'leave_page', '检测到您离开了考试页面', '2026-06-10 09:47:56', '2026-06-10 09:47:56');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (5, 11, 5, NULL, 'exit_fullscreen', '检测到退出全屏模式', '2026-06-10 09:47:56', '2026-06-10 09:47:56');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (6, 11, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-10 09:48:01', '2026-06-10 09:48:01');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (7, 11, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-10 09:48:14', '2026-06-10 09:48:14');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (8, 11, 5, NULL, 'leave_page', '检测到您离开了考试页面', '2026-06-10 09:48:14', '2026-06-10 09:48:14');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (9, 11, 5, NULL, 'exit_fullscreen', '检测到退出全屏模式', '2026-06-10 09:48:16', '2026-06-10 09:48:16');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (10, 11, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-10 09:48:21', '2026-06-10 09:48:21');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (11, 11, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-10 09:48:35', '2026-06-10 09:48:35');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (12, 11, 5, NULL, 'exit_fullscreen', '检测到退出全屏模式', '2026-06-10 09:48:56', '2026-06-10 09:48:56');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (13, 11, 5, NULL, 'exit_fullscreen', '检测到退出全屏模式', '2026-06-10 09:58:08', '2026-06-10 09:58:08');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (14, 11, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-10 09:58:15', '2026-06-10 09:58:15');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (15, 11, 5, NULL, 'leave_page', '检测到您离开了考试页面', '2026-06-10 09:58:15', '2026-06-10 09:58:15');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (16, 11, 5, NULL, 'exit_fullscreen', '检测到退出全屏模式', '2026-06-10 09:58:29', '2026-06-10 09:58:29');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (17, 11, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-10 09:58:31', '2026-06-10 09:58:31');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (18, 11, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-10 09:58:34', '2026-06-10 09:58:34');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (19, 11, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-10 09:58:36', '2026-06-10 09:58:36');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (20, 11, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-10 09:58:38', '2026-06-10 09:58:38');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (21, 11, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-10 09:58:39', '2026-06-10 09:58:39');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (22, 11, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-10 09:58:40', '2026-06-10 09:58:40');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (23, 11, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-10 09:58:40', '2026-06-10 09:58:40');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (24, 11, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-10 10:03:00', '2026-06-10 10:03:00');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (25, 11, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-10 10:03:12', '2026-06-10 10:03:12');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (26, 11, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-10 10:10:10', '2026-06-10 10:10:10');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (27, 11, 5, NULL, 'exit_fullscreen', '检测到退出全屏模式', '2026-06-10 10:10:11', '2026-06-10 10:10:11');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (28, 11, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-10 10:10:27', '2026-06-10 10:10:27');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (29, 11, 5, NULL, 'leave_page', '检测到您离开了考试页面', '2026-06-10 10:10:27', '2026-06-10 10:10:27');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (30, 11, 5, NULL, 'exit_fullscreen', '检测到退出全屏模式', '2026-06-10 10:10:30', '2026-06-10 10:10:30');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (31, 11, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-10 10:10:38', '2026-06-10 10:10:38');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (32, 11, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-10 10:11:43', '2026-06-10 10:11:43');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (33, 11, 5, NULL, 'exit_fullscreen', '检测到退出全屏模式', '2026-06-10 10:11:43', '2026-06-10 10:11:43');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (34, 11, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-10 10:11:47', '2026-06-10 10:11:47');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (35, 11, 5, NULL, 'exit_fullscreen', '检测到退出全屏模式', '2026-06-10 10:11:48', '2026-06-10 10:11:48');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (36, 11, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-10 10:19:49', '2026-06-10 10:19:49');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (37, 11, 5, NULL, 'exit_fullscreen', '检测到退出全屏模式', '2026-06-10 10:19:50', '2026-06-10 10:19:50');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (38, 11, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-10 10:20:15', '2026-06-10 10:20:15');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (39, 11, 5, NULL, 'leave_page', '检测到您离开了考试页面', '2026-06-10 10:20:16', '2026-06-10 10:20:16');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (40, 11, 5, NULL, 'exit_fullscreen', '检测到退出全屏模式', '2026-06-10 10:20:16', '2026-06-10 10:20:16');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (41, 11, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-10 10:20:28', '2026-06-10 10:20:28');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (42, 11, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-10 10:20:52', '2026-06-10 10:20:52');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (43, 11, 5, NULL, 'leave_page', '检测到您离开了考试页面', '2026-06-10 10:20:52', '2026-06-10 10:20:52');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (44, 11, 5, NULL, 'exit_fullscreen', '检测到退出全屏模式', '2026-06-10 10:20:53', '2026-06-10 10:20:53');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (45, 11, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-10 10:20:54', '2026-06-10 10:20:54');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (46, 11, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-10 10:21:06', '2026-06-10 10:21:06');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (47, 11, 5, NULL, 'exit_fullscreen', '检测到退出全屏模式', '2026-06-10 10:21:08', '2026-06-10 10:21:08');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (48, 11, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-10 10:21:12', '2026-06-10 10:21:12');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (49, 11, 5, NULL, 'exit_fullscreen', '检测到退出全屏模式', '2026-06-10 10:21:15', '2026-06-10 10:21:15');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (50, 11, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-10 10:21:19', '2026-06-10 10:21:19');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (51, 11, 5, NULL, 'exit_fullscreen', '检测到退出全屏模式', '2026-06-10 10:22:01', '2026-06-10 10:22:01');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (52, 11, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-10 10:22:10', '2026-06-10 10:22:10');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (53, 11, 5, NULL, 'leave_page', '检测到您离开了考试页面', '2026-06-10 10:22:10', '2026-06-10 10:22:10');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (54, 11, 5, NULL, 'exit_fullscreen', '检测到退出全屏模式', '2026-06-10 10:22:11', '2026-06-10 10:22:11');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (55, 11, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-10 10:22:27', '2026-06-10 10:22:27');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (56, 4, 5, NULL, 'exit_fullscreen', '检测到退出全屏模式', '2026-06-10 10:29:23', '2026-06-10 10:29:23');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (57, 4, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-10 10:29:42', '2026-06-10 10:29:42');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (58, 4, 5, NULL, 'leave_page', '检测到您离开了考试页面', '2026-06-10 10:29:42', '2026-06-10 10:29:42');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (59, 4, 5, NULL, 'exit_fullscreen', '检测到退出全屏模式', '2026-06-10 10:29:43', '2026-06-10 10:29:43');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (60, 4, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-10 10:29:46', '2026-06-10 10:29:46');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (61, 4, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-10 10:30:24', '2026-06-10 10:30:24');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (62, 4, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-10 10:30:49', '2026-06-10 10:30:49');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (63, 4, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-10 10:31:01', '2026-06-10 10:31:01');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (64, 4, 5, NULL, 'exit_fullscreen', '检测到退出全屏模式', '2026-06-10 10:31:07', '2026-06-10 10:31:07');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (65, 4, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-10 10:31:10', '2026-06-10 10:31:10');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (66, 11, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-10 10:37:46', '2026-06-10 10:37:46');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (67, 11, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-10 10:37:50', '2026-06-10 10:37:50');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (68, 11, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-10 10:37:53', '2026-06-10 10:37:53');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (69, 11, 5, NULL, 'exit_fullscreen', '检测到退出全屏模式', '2026-06-10 10:37:57', '2026-06-10 10:37:57');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (70, 11, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-10 10:38:04', '2026-06-10 10:38:04');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (71, 11, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-10 10:38:08', '2026-06-10 10:38:08');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (72, 11, 5, NULL, 'exit_fullscreen', '检测到退出全屏模式', '2026-06-10 10:38:11', '2026-06-10 10:38:11');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (73, 11, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-10 10:38:14', '2026-06-10 10:38:14');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (74, 11, 5, NULL, 'exit_fullscreen', '检测到退出全屏模式', '2026-06-10 10:38:21', '2026-06-10 10:38:21');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (75, 11, 5, NULL, 'exit_fullscreen', '检测到退出全屏模式', '2026-06-10 10:41:58', '2026-06-10 10:41:58');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (76, 11, 5, NULL, 'exit_fullscreen', '检测到退出全屏模式', '2026-06-10 10:42:02', '2026-06-10 10:42:02');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (77, 11, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-10 10:42:16', '2026-06-10 10:42:16');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (78, 11, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-10 10:42:18', '2026-06-10 10:42:18');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (79, 11, 5, NULL, 'leave_page', '检测到您离开了考试页面', '2026-06-10 10:42:24', '2026-06-10 10:42:24');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (80, 11, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-10 10:42:28', '2026-06-10 10:42:28');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (81, 11, 5, NULL, 'leave_page', '检测到您离开了考试页面', '2026-06-10 10:42:29', '2026-06-10 10:42:29');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (82, 11, 5, NULL, 'exit_fullscreen', '检测到退出全屏模式', '2026-06-10 10:42:36', '2026-06-10 10:42:36');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (83, 11, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-10 10:42:39', '2026-06-10 10:42:39');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (84, 11, 5, NULL, 'leave_page', '检测到您离开了考试页面', '2026-06-10 10:42:39', '2026-06-10 10:42:39');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (85, 11, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-10 10:42:44', '2026-06-10 10:42:44');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (86, 11, 5, NULL, 'leave_page', '检测到您离开了考试页面', '2026-06-10 10:42:44', '2026-06-10 10:42:44');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (87, 11, 5, NULL, 'exit_fullscreen', '检测到退出全屏模式', '2026-06-10 10:43:44', '2026-06-10 10:43:44');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (88, 11, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-10 10:44:13', '2026-06-10 10:44:13');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (89, 11, 5, NULL, 'leave_page', '检测到您离开了考试页面', '2026-06-10 10:44:13', '2026-06-10 10:44:13');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (90, 11, 5, NULL, 'exit_fullscreen', '检测到退出全屏模式', '2026-06-10 10:44:17', '2026-06-10 10:44:17');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (91, 11, 5, NULL, 'leave_page', '检测到您离开了考试页面', '2026-06-10 10:44:19', '2026-06-10 10:44:19');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (92, 11, 5, NULL, 'exit_fullscreen', '检测到退出全屏模式', '2026-06-10 13:27:39', '2026-06-10 13:27:39');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (93, 11, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-10 13:30:38', '2026-06-10 13:30:38');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (94, 8, 5, NULL, 'exit_fullscreen', '检测到退出全屏模式', '2026-06-10 15:50:42', '2026-06-10 15:50:42');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (95, 8, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-10 15:50:45', '2026-06-10 15:50:45');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (96, 8, 5, NULL, 'exit_fullscreen', '检测到退出全屏模式', '2026-06-10 15:57:52', '2026-06-10 15:57:52');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (97, 8, 5, NULL, 'exit_fullscreen', '检测到退出全屏模式', '2026-06-10 15:58:00', '2026-06-10 15:58:00');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (98, 8, 5, NULL, 'exit_fullscreen', '检测到退出全屏模式', '2026-06-10 15:58:03', '2026-06-10 15:58:03');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (99, 11, 5, NULL, 'exit_fullscreen', '检测到退出全屏模式', '2026-06-11 00:33:03', '2026-06-11 00:33:03');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (100, 11, 5, NULL, 'exit_fullscreen', '检测到退出全屏模式', '2026-06-11 00:33:06', '2026-06-11 00:33:06');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (101, 11, 5, NULL, 'exit_fullscreen', '检测到退出全屏模式', '2026-06-11 00:33:16', '2026-06-11 00:33:16');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (102, 11, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-11 00:53:54', '2026-06-11 00:53:54');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (103, 4, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-11 00:57:31', '2026-06-11 00:57:31');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (104, 4, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-11 01:01:13', '2026-06-11 01:01:13');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (105, 4, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-11 02:14:29', '2026-06-11 02:14:29');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (106, 4, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-11 02:14:49', '2026-06-11 02:14:49');
INSERT INTO `exam_cheats` (`id`, `user_id`, `exam_id`, `exam_record_id`, `action`, `detail`, `created_at`, `updated_at`) VALUES (107, 4, 5, NULL, 'blur', '检测到浏览器窗口失去焦点', '2026-06-11 02:14:55', '2026-06-11 02:14:55');
COMMIT;

-- ----------------------------
-- Table structure for exam_courses
-- ----------------------------
DROP TABLE IF EXISTS `exam_courses`;
CREATE TABLE `exam_courses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `exam_id` bigint unsigned NOT NULL COMMENT '考试ID',
  `course_id` bigint unsigned NOT NULL COMMENT '课程ID',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `exam_courses_exam_id_course_id_unique` (`exam_id`,`course_id`),
  KEY `exam_courses_course_id_foreign` (`course_id`),
  CONSTRAINT `exam_courses_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `exam_courses_exam_id_foreign` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='考试-课程关联表';

-- ----------------------------
-- Records of exam_courses
-- ----------------------------
BEGIN;
INSERT INTO `exam_courses` (`id`, `exam_id`, `course_id`, `created_at`, `updated_at`) VALUES (1, 5, 11, '2026-06-11 02:12:18', '2026-06-11 02:12:18');
INSERT INTO `exam_courses` (`id`, `exam_id`, `course_id`, `created_at`, `updated_at`) VALUES (2, 5, 14, NULL, NULL);
INSERT INTO `exam_courses` (`id`, `exam_id`, `course_id`, `created_at`, `updated_at`) VALUES (3, 5, 12, NULL, NULL);
COMMIT;

-- ----------------------------
-- Table structure for exam_records
-- ----------------------------
DROP TABLE IF EXISTS `exam_records`;
CREATE TABLE `exam_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `user_id` bigint unsigned NOT NULL COMMENT '学员用户ID',
  `exam_id` bigint unsigned NOT NULL COMMENT '考试ID',
  `answers` json NOT NULL COMMENT '答题详情',
  `objective_score` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT '客观题自动评分',
  `subjective_score` decimal(5,2) DEFAULT NULL COMMENT '主观题人工评分',
  `total_score` decimal(5,2) DEFAULT NULL COMMENT '总分',
  `status` enum('in_progress','submitted','auto_graded','pending_review','graded','rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '状态：in_progress=答题中, submitted=已提交, auto_graded=已自动评分, pending_review=待人工批改, graded=已评分, rejected=已驳回',
  `submitted_at` timestamp NULL DEFAULT NULL COMMENT '提交时间',
  `graded_at` timestamp NULL DEFAULT NULL COMMENT '评分完成时间',
  `graded_by` bigint unsigned DEFAULT NULL COMMENT '评分人用户ID',
  `assigned_to` bigint unsigned DEFAULT NULL COMMENT '分配给谁批改',
  `assignment_note` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '分配备注',
  `mentor_comment` text COLLATE utf8mb4_unicode_ci COMMENT '导师评语',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `exam_records_exam_id_foreign` (`exam_id`),
  KEY `exam_records_user_id_exam_id_index` (`user_id`,`exam_id`),
  KEY `exam_records_status_index` (`status`),
  KEY `exam_records_graded_by_index` (`graded_by`),
  CONSTRAINT `exam_records_exam_id_foreign` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `exam_records_graded_by_foreign` FOREIGN KEY (`graded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `exam_records_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='考试记录表：记录学员考试成绩和状态';

-- ----------------------------
-- Records of exam_records
-- ----------------------------
BEGIN;
INSERT INTO `exam_records` (`id`, `user_id`, `exam_id`, `answers`, `objective_score`, `subjective_score`, `total_score`, `status`, `submitted_at`, `graded_at`, `graded_by`, `assigned_to`, `assignment_note`, `mentor_comment`, `created_at`, `updated_at`) VALUES (7, 8, 5, '[{\"answer\": \"yqtutqyttututu\", \"is_correct\": null, \"auto_graded\": true, \"question_id\": 7, \"score_awarded\": 0}, {\"answer\": \"qweqeqe\", \"is_correct\": null, \"auto_graded\": true, \"question_id\": 8, \"score_awarded\": 0}, {\"answer\": \"qweqwe\", \"is_correct\": null, \"auto_graded\": true, \"question_id\": 9, \"score_awarded\": 0}, {\"answer\": \"qweqweqeweq\", \"is_correct\": null, \"auto_graded\": true, \"question_id\": 10, \"score_awarded\": 0}, {\"answer\": \"qweqweeq\", \"is_correct\": null, \"auto_graded\": true, \"question_id\": 11, \"score_awarded\": 0}, {\"answer\": \"afafaffaf\", \"is_correct\": null, \"auto_graded\": true, \"question_id\": 12, \"score_awarded\": 0}, {\"answer\": \"qeqqeqeqew\", \"is_correct\": null, \"auto_graded\": true, \"question_id\": 13, \"score_awarded\": 0}, {\"answer\": \"qeqeqeeqqwee\", \"is_correct\": null, \"auto_graded\": true, \"question_id\": 14, \"score_awarded\": 0}, {\"answer\": \"qewqeqeqew\", \"is_correct\": null, \"auto_graded\": true, \"question_id\": 15, \"score_awarded\": 0.5}, {\"answer\": [\"前锁\", \"前锁\", \"后锁\", \"后锁\"], \"is_correct\": true, \"question_id\": 16, \"score_awarded\": 10}]', 10.00, NULL, NULL, 'rejected', '2026-06-09 11:22:31', '2026-06-09 21:49:59', 4, 7, NULL, '答案太敷衍', '2026-06-09 11:22:31', '2026-06-09 21:49:59');
INSERT INTO `exam_records` (`id`, `user_id`, `exam_id`, `answers`, `objective_score`, `subjective_score`, `total_score`, `status`, `submitted_at`, `graded_at`, `graded_by`, `assigned_to`, `assignment_note`, `mentor_comment`, `created_at`, `updated_at`) VALUES (8, 12, 5, '[{\"answer\": \"测试\", \"is_correct\": null, \"auto_graded\": true, \"question_id\": 7, \"score_awarded\": 0}, {\"answer\": \"测试\", \"is_correct\": null, \"auto_graded\": true, \"question_id\": 8, \"score_awarded\": 0}, {\"answer\": \"测试\", \"is_correct\": null, \"auto_graded\": true, \"question_id\": 9, \"score_awarded\": 0}, {\"answer\": \"测试\", \"is_correct\": null, \"auto_graded\": true, \"question_id\": 10, \"score_awarded\": 0}, {\"answer\": \"测试\", \"is_correct\": null, \"auto_graded\": true, \"question_id\": 11, \"score_awarded\": 0}, {\"answer\": \"从而实现\", \"is_correct\": null, \"auto_graded\": true, \"question_id\": 12, \"score_awarded\": 0}, {\"answer\": \"测试\", \"is_correct\": null, \"auto_graded\": true, \"question_id\": 13, \"score_awarded\": 0}, {\"answer\": \"测试\", \"is_correct\": null, \"auto_graded\": true, \"question_id\": 14, \"score_awarded\": 0}, {\"answer\": \"测试\", \"is_correct\": null, \"auto_graded\": true, \"question_id\": 15, \"score_awarded\": 0}, {\"answer\": [\"前\", null, \"222222222222222222222\"], \"is_correct\": null, \"auto_graded\": true, \"question_id\": 16, \"score_awarded\": 0}]', 0.00, NULL, NULL, 'pending_review', '2026-06-10 06:10:18', NULL, NULL, 4, NULL, NULL, '2026-06-10 06:10:18', '2026-06-10 06:10:18');
INSERT INTO `exam_records` (`id`, `user_id`, `exam_id`, `answers`, `objective_score`, `subjective_score`, `total_score`, `status`, `submitted_at`, `graded_at`, `graded_by`, `assigned_to`, `assignment_note`, `mentor_comment`, `created_at`, `updated_at`) VALUES (9, 11, 5, '[{\"answer\": null, \"is_correct\": null, \"auto_graded\": true, \"question_id\": 7, \"score_awarded\": 0}, {\"answer\": null, \"is_correct\": null, \"auto_graded\": true, \"question_id\": 8, \"score_awarded\": 0}, {\"answer\": null, \"is_correct\": null, \"auto_graded\": true, \"question_id\": 9, \"score_awarded\": 0}, {\"answer\": null, \"is_correct\": null, \"auto_graded\": true, \"question_id\": 10, \"score_awarded\": 0}, {\"answer\": null, \"is_correct\": null, \"auto_graded\": true, \"question_id\": 11, \"score_awarded\": 0}, {\"answer\": null, \"is_correct\": null, \"auto_graded\": true, \"question_id\": 12, \"score_awarded\": 0}, {\"answer\": null, \"is_correct\": null, \"auto_graded\": true, \"question_id\": 13, \"score_awarded\": 0}, {\"answer\": null, \"is_correct\": null, \"auto_graded\": true, \"question_id\": 14, \"score_awarded\": 0}, {\"answer\": [null, null, null, null, null, null, null, null, null, null], \"is_correct\": null, \"auto_graded\": true, \"question_id\": 15, \"score_awarded\": 0}, {\"answer\": [null, null, null, null], \"is_correct\": null, \"auto_graded\": true, \"question_id\": 16, \"score_awarded\": 0}]', 0.00, NULL, NULL, 'rejected', '2026-06-10 10:11:49', '2026-06-10 10:19:14', 4, 7, NULL, '重新考试', '2026-06-10 10:11:49', '2026-06-10 10:19:14');
INSERT INTO `exam_records` (`id`, `user_id`, `exam_id`, `answers`, `objective_score`, `subjective_score`, `total_score`, `status`, `submitted_at`, `graded_at`, `graded_by`, `assigned_to`, `assignment_note`, `mentor_comment`, `created_at`, `updated_at`) VALUES (10, 8, 5, '[{\"answer\": null, \"is_correct\": null, \"auto_graded\": true, \"question_id\": 7, \"score_awarded\": 0}, {\"answer\": null, \"is_correct\": null, \"auto_graded\": true, \"question_id\": 8, \"score_awarded\": 0}, {\"answer\": null, \"is_correct\": null, \"auto_graded\": true, \"question_id\": 9, \"score_awarded\": 0}, {\"answer\": null, \"is_correct\": null, \"auto_graded\": true, \"question_id\": 10, \"score_awarded\": 0}, {\"answer\": null, \"is_correct\": null, \"auto_graded\": true, \"question_id\": 11, \"score_awarded\": 0}, {\"answer\": null, \"is_correct\": null, \"auto_graded\": true, \"question_id\": 12, \"score_awarded\": 0}, {\"answer\": null, \"is_correct\": null, \"auto_graded\": true, \"question_id\": 13, \"score_awarded\": 0}, {\"answer\": null, \"is_correct\": null, \"auto_graded\": true, \"question_id\": 14, \"score_awarded\": 0}, {\"answer\": null, \"is_correct\": null, \"auto_graded\": true, \"question_id\": 15, \"score_awarded\": 0}, {\"answer\": null, \"is_correct\": null, \"auto_graded\": true, \"question_id\": 16, \"score_awarded\": 0}]', 0.00, NULL, NULL, 'rejected', '2026-06-10 15:58:05', '2026-06-10 17:16:00', 7, 7, NULL, '空白卷', '2026-06-10 15:58:05', '2026-06-10 17:16:00');
INSERT INTO `exam_records` (`id`, `user_id`, `exam_id`, `answers`, `objective_score`, `subjective_score`, `total_score`, `status`, `submitted_at`, `graded_at`, `graded_by`, `assigned_to`, `assignment_note`, `mentor_comment`, `created_at`, `updated_at`) VALUES (11, 11, 5, '[{\"answer\": \"111\", \"is_correct\": null, \"auto_graded\": true, \"question_id\": 7, \"score_awarded\": 0}, {\"answer\": \"111\", \"is_correct\": null, \"auto_graded\": true, \"question_id\": 8, \"score_awarded\": 0}, {\"answer\": null, \"is_correct\": null, \"auto_graded\": true, \"question_id\": 9, \"score_awarded\": 0}, {\"answer\": null, \"is_correct\": null, \"auto_graded\": true, \"question_id\": 10, \"score_awarded\": 0}, {\"answer\": null, \"is_correct\": null, \"auto_graded\": true, \"question_id\": 11, \"score_awarded\": 0}, {\"answer\": null, \"is_correct\": null, \"auto_graded\": true, \"question_id\": 12, \"score_awarded\": 0}, {\"answer\": null, \"is_correct\": null, \"auto_graded\": true, \"question_id\": 13, \"score_awarded\": 0}, {\"answer\": null, \"is_correct\": null, \"auto_graded\": true, \"question_id\": 14, \"score_awarded\": 0}, {\"answer\": null, \"is_correct\": null, \"auto_graded\": true, \"question_id\": 15, \"score_awarded\": 0}, {\"answer\": null, \"is_correct\": null, \"auto_graded\": true, \"question_id\": 16, \"score_awarded\": 0}]', 0.00, NULL, NULL, 'rejected', '2026-06-11 00:33:18', '2026-06-11 00:41:09', 7, 7, NULL, '重考', '2026-06-11 00:33:18', '2026-06-11 00:41:09');
INSERT INTO `exam_records` (`id`, `user_id`, `exam_id`, `answers`, `objective_score`, `subjective_score`, `total_score`, `status`, `submitted_at`, `graded_at`, `graded_by`, `assigned_to`, `assignment_note`, `mentor_comment`, `created_at`, `updated_at`) VALUES (12, 11, 5, '[{\"answer\": \"连接器是连接两个有源器件，能传输电流、数据或信号的器件，\", \"is_correct\": null, \"auto_graded\": true, \"question_id\": 7, \"score_awarded\": 7.5}, {\"answer\": \"改善生产进程、易于修理、便于升级、设计的灵活性\", \"is_correct\": null, \"auto_graded\": true, \"question_id\": 8, \"score_awarded\": 10}, {\"answer\": \"泰科、莫仕、安费诺\", \"is_correct\": null, \"auto_graded\": true, \"question_id\": 9, \"score_awarded\": 10}, {\"answer\": \"汽车、通讯、消费电子、工业\", \"is_correct\": null, \"auto_graded\": true, \"question_id\": 10, \"score_awarded\": 4}, {\"answer\": \"连接器主要由接触件/中心针、绝缘体、外壳三部分组成\\n连接器配件有：螺丝、螺母、垫片、防尘帽\", \"is_correct\": null, \"auto_graded\": true, \"question_id\": 11, \"score_awarded\": 10}, {\"answer\": \"1、机械性能：使用寿命、插拔力\\n2、电气性能：额定电流、耐受电压、插入损耗\\n3、环境性能：耐温、耐湿、耐盐雾、振动、冲击\", \"is_correct\": null, \"auto_graded\": true, \"question_id\": 12, \"score_awarded\": 10}, {\"answer\": \"公母、直弯、针数、对接方式\", \"is_correct\": null, \"auto_graded\": true, \"question_id\": 13, \"score_awarded\": 10}, {\"answer\": \"1、面板安装：穿墙、法兰\\n2、接线安装：模具成型、组装\", \"is_correct\": null, \"auto_graded\": true, \"question_id\": 14, \"score_awarded\": 10}, {\"answer\": [\"connertoy\", \"adapter\", \"plug\", \"socket\", \"male pin\", \"female pin\", \"insulator\", \"mounting feature\", \"bulkhead\", \"flange mount\"], \"is_correct\": null, \"auto_graded\": true, \"question_id\": 15, \"score_awarded\": 0}, {\"answer\": [\"前锁\", \"前锁\", \"后锁\", \"后锁\"], \"is_correct\": null, \"auto_graded\": true, \"question_id\": 16, \"score_awarded\": 10}]', 0.00, NULL, NULL, 'pending_review', '2026-06-11 00:55:25', NULL, NULL, 7, NULL, NULL, '2026-06-11 00:55:25', '2026-06-11 00:55:25');
INSERT INTO `exam_records` (`id`, `user_id`, `exam_id`, `answers`, `objective_score`, `subjective_score`, `total_score`, `status`, `submitted_at`, `graded_at`, `graded_by`, `assigned_to`, `assignment_note`, `mentor_comment`, `created_at`, `updated_at`) VALUES (13, 4, 5, '[{\"answer\": null, \"is_correct\": null, \"auto_graded\": true, \"question_id\": 7, \"score_awarded\": 0}, {\"answer\": null, \"is_correct\": null, \"auto_graded\": true, \"question_id\": 8, \"score_awarded\": 0}, {\"answer\": null, \"is_correct\": null, \"auto_graded\": true, \"question_id\": 9, \"score_awarded\": 0}, {\"answer\": null, \"is_correct\": null, \"auto_graded\": true, \"question_id\": 10, \"score_awarded\": 0}, {\"answer\": null, \"is_correct\": null, \"auto_graded\": true, \"question_id\": 11, \"score_awarded\": 0}, {\"answer\": null, \"is_correct\": null, \"auto_graded\": true, \"question_id\": 12, \"score_awarded\": 0}, {\"answer\": null, \"is_correct\": null, \"auto_graded\": true, \"question_id\": 13, \"score_awarded\": 0}, {\"answer\": null, \"is_correct\": null, \"auto_graded\": true, \"question_id\": 14, \"score_awarded\": 0}, {\"answer\": [null, null, null, null, null, null, null, null, null, null], \"is_correct\": null, \"auto_graded\": true, \"question_id\": 15, \"score_awarded\": 0}, {\"answer\": [null, null, null, null], \"is_correct\": null, \"auto_graded\": true, \"question_id\": 16, \"score_awarded\": 0}]', 0.00, NULL, NULL, 'pending_review', '2026-06-11 02:14:57', NULL, NULL, 4, NULL, NULL, '2026-06-11 02:14:57', '2026-06-11 02:14:57');
COMMIT;

-- ----------------------------
-- Table structure for exams
-- ----------------------------
DROP TABLE IF EXISTS `exams`;
CREATE TABLE `exams` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '考试标题',
  `time_limit` int unsigned NOT NULL COMMENT '考试时长(分钟)',
  `passing_score` decimal(5,2) NOT NULL DEFAULT '60.00' COMMENT '及格分数线',
  `status` enum('draft','active','archived') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft' COMMENT '状态：draft=草稿, active=进行中, archived=已归档',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `exams_status_index` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='考试表：关联课程，设置时长和及格分';

-- ----------------------------
-- Records of exams
-- ----------------------------
BEGIN;
INSERT INTO `exams` (`id`, `title`, `time_limit`, `passing_score`, `status`, `created_at`, `updated_at`) VALUES (5, '连接器导论试题', 60, 80.00, 'active', '2026-06-09 09:17:27', '2026-06-10 09:56:24');
COMMIT;

-- ----------------------------
-- Table structure for failed_jobs
-- ----------------------------
DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '任务UUID',
  `connection` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '连接名称',
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '队列名称',
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '任务负载',
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '异常信息',
  `failed_at` timestamp NOT NULL COMMENT '失败时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Laravel 失败任务表';

-- ----------------------------
-- Records of failed_jobs
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for job_batches
-- ----------------------------
DROP TABLE IF EXISTS `job_batches`;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '批次ID',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '批次名称',
  `total_jobs` int NOT NULL COMMENT '总任务数',
  `pending_jobs` int NOT NULL COMMENT '待处理任务数',
  `failed_jobs` int NOT NULL COMMENT '失败任务数',
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '失败任务ID列表',
  `options` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '选项',
  `cancelled_at` int DEFAULT NULL COMMENT '取消时间',
  `created_at` int NOT NULL COMMENT '创建时间',
  `finished_at` int DEFAULT NULL COMMENT '完成时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Laravel 任务批次表';

-- ----------------------------
-- Records of job_batches
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for jobs
-- ----------------------------
DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '队列名称',
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '任务负载',
  `attempts` tinyint unsigned NOT NULL COMMENT '尝试次数',
  `reserved_at` int unsigned DEFAULT NULL COMMENT '预留时间',
  `available_at` int unsigned NOT NULL COMMENT '可用时间',
  `created_at` int unsigned NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Laravel 任务表';

-- ----------------------------
-- Records of jobs
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for learning_progress
-- ----------------------------
DROP TABLE IF EXISTS `learning_progress`;
CREATE TABLE `learning_progress` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `user_id` bigint unsigned NOT NULL COMMENT '学员用户ID',
  `course_id` bigint unsigned NOT NULL COMMENT '课程ID',
  `is_completed` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否已完成：0=未完成, 1=已完成',
  `progress_percentage` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT '学习进度百分比',
  `total_learning_time` int unsigned NOT NULL DEFAULT '0' COMMENT '累计学习秒数',
  `last_position_seconds` int unsigned NOT NULL DEFAULT '0' COMMENT '视频上次播放位置(秒)',
  `completed_at` timestamp NULL DEFAULT NULL COMMENT '完成时间',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `learning_progress_user_id_course_id_unique` (`user_id`,`course_id`),
  KEY `learning_progress_user_id_index` (`user_id`),
  KEY `learning_progress_course_id_index` (`course_id`),
  KEY `learning_progress_is_completed_index` (`is_completed`),
  CONSTRAINT `learning_progress_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `learning_progress_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='学习进度表：记录学员课程学习进度和时长';

-- ----------------------------
-- Records of learning_progress
-- ----------------------------
BEGIN;
INSERT INTO `learning_progress` (`id`, `user_id`, `course_id`, `is_completed`, `progress_percentage`, `total_learning_time`, `last_position_seconds`, `completed_at`, `created_at`, `updated_at`) VALUES (15, 4, 11, 0, 100.00, 955, 0, NULL, '2026-06-09 08:26:31', '2026-06-10 14:39:20');
INSERT INTO `learning_progress` (`id`, `user_id`, `course_id`, `is_completed`, `progress_percentage`, `total_learning_time`, `last_position_seconds`, `completed_at`, `created_at`, `updated_at`) VALUES (16, 8, 11, 1, 100.00, 30, 728, '2026-06-09 11:10:06', '2026-06-09 10:58:05', '2026-06-10 13:46:31');
INSERT INTO `learning_progress` (`id`, `user_id`, `course_id`, `is_completed`, `progress_percentage`, `total_learning_time`, `last_position_seconds`, `completed_at`, `created_at`, `updated_at`) VALUES (17, 8, 13, 0, 4.99, 45, 0, NULL, '2026-06-09 11:12:18', '2026-06-11 01:14:20');
INSERT INTO `learning_progress` (`id`, `user_id`, `course_id`, `is_completed`, `progress_percentage`, `total_learning_time`, `last_position_seconds`, `completed_at`, `created_at`, `updated_at`) VALUES (18, 7, 11, 0, 31.32, 228, 0, NULL, '2026-06-09 12:04:13', '2026-06-09 20:58:48');
INSERT INTO `learning_progress` (`id`, `user_id`, `course_id`, `is_completed`, `progress_percentage`, `total_learning_time`, `last_position_seconds`, `completed_at`, `created_at`, `updated_at`) VALUES (19, 11, 15, 0, 20.66, 262, 0, NULL, '2026-06-09 14:55:05', '2026-06-10 18:00:52');
INSERT INTO `learning_progress` (`id`, `user_id`, `course_id`, `is_completed`, `progress_percentage`, `total_learning_time`, `last_position_seconds`, `completed_at`, `created_at`, `updated_at`) VALUES (20, 7, 12, 0, 100.00, 10, 0, NULL, '2026-06-09 15:09:08', '2026-06-09 15:09:08');
INSERT INTO `learning_progress` (`id`, `user_id`, `course_id`, `is_completed`, `progress_percentage`, `total_learning_time`, `last_position_seconds`, `completed_at`, `created_at`, `updated_at`) VALUES (21, 7, 15, 0, 100.00, 2570, 0, NULL, '2026-06-09 15:09:23', '2026-06-09 20:58:14');
INSERT INTO `learning_progress` (`id`, `user_id`, `course_id`, `is_completed`, `progress_percentage`, `total_learning_time`, `last_position_seconds`, `completed_at`, `created_at`, `updated_at`) VALUES (22, 11, 11, 1, 0.43, 30, 3, '2026-06-10 07:56:40', '2026-06-09 21:12:51', '2026-06-10 13:46:31');
INSERT INTO `learning_progress` (`id`, `user_id`, `course_id`, `is_completed`, `progress_percentage`, `total_learning_time`, `last_position_seconds`, `completed_at`, `created_at`, `updated_at`) VALUES (23, 4, 15, 0, 2.79, 40, 0, NULL, '2026-06-10 01:19:06', '2026-06-10 14:24:45');
INSERT INTO `learning_progress` (`id`, `user_id`, `course_id`, `is_completed`, `progress_percentage`, `total_learning_time`, `last_position_seconds`, `completed_at`, `created_at`, `updated_at`) VALUES (24, 4, 16, 1, 100.00, 30, 0, '2026-06-10 01:19:11', '2026-06-10 01:19:11', '2026-06-10 13:46:31');
INSERT INTO `learning_progress` (`id`, `user_id`, `course_id`, `is_completed`, `progress_percentage`, `total_learning_time`, `last_position_seconds`, `completed_at`, `created_at`, `updated_at`) VALUES (25, 10, 11, 0, 6.46, 57, 0, NULL, '2026-06-10 01:22:37', '2026-06-11 02:57:59');
INSERT INTO `learning_progress` (`id`, `user_id`, `course_id`, `is_completed`, `progress_percentage`, `total_learning_time`, `last_position_seconds`, `completed_at`, `created_at`, `updated_at`) VALUES (26, 10, 12, 0, 100.00, 200, 0, NULL, '2026-06-10 01:22:56', '2026-06-10 01:26:06');
INSERT INTO `learning_progress` (`id`, `user_id`, `course_id`, `is_completed`, `progress_percentage`, `total_learning_time`, `last_position_seconds`, `completed_at`, `created_at`, `updated_at`) VALUES (27, 4, 17, 0, 100.00, 170, 0, NULL, '2026-06-10 01:35:02', '2026-06-10 01:37:34');
INSERT INTO `learning_progress` (`id`, `user_id`, `course_id`, `is_completed`, `progress_percentage`, `total_learning_time`, `last_position_seconds`, `completed_at`, `created_at`, `updated_at`) VALUES (28, 4, 14, 0, 100.00, 420, 0, NULL, '2026-06-10 01:41:23', '2026-06-10 01:48:08');
INSERT INTO `learning_progress` (`id`, `user_id`, `course_id`, `is_completed`, `progress_percentage`, `total_learning_time`, `last_position_seconds`, `completed_at`, `created_at`, `updated_at`) VALUES (29, 12, 9, 0, 100.00, 110, 0, NULL, '2026-06-10 02:02:18', '2026-06-10 02:03:36');
INSERT INTO `learning_progress` (`id`, `user_id`, `course_id`, `is_completed`, `progress_percentage`, `total_learning_time`, `last_position_seconds`, `completed_at`, `created_at`, `updated_at`) VALUES (30, 12, 10, 0, 13.33, 10, 0, NULL, '2026-06-10 02:03:31', '2026-06-10 02:03:31');
INSERT INTO `learning_progress` (`id`, `user_id`, `course_id`, `is_completed`, `progress_percentage`, `total_learning_time`, `last_position_seconds`, `completed_at`, `created_at`, `updated_at`) VALUES (31, 12, 11, 1, 100.00, 30, 728, '2026-06-10 02:43:01', '2026-06-10 02:10:14', '2026-06-10 13:46:31');
INSERT INTO `learning_progress` (`id`, `user_id`, `course_id`, `is_completed`, `progress_percentage`, `total_learning_time`, `last_position_seconds`, `completed_at`, `created_at`, `updated_at`) VALUES (32, 12, 12, 1, 100.00, 30, 0, '2026-06-10 02:11:17', '2026-06-10 02:10:49', '2026-06-10 13:46:31');
INSERT INTO `learning_progress` (`id`, `user_id`, `course_id`, `is_completed`, `progress_percentage`, `total_learning_time`, `last_position_seconds`, `completed_at`, `created_at`, `updated_at`) VALUES (33, 12, 13, 0, 4.70, 49, 0, NULL, '2026-06-10 02:12:54', '2026-06-10 02:40:37');
INSERT INTO `learning_progress` (`id`, `user_id`, `course_id`, `is_completed`, `progress_percentage`, `total_learning_time`, `last_position_seconds`, `completed_at`, `created_at`, `updated_at`) VALUES (34, 12, 15, 0, 0.08, 10, 0, NULL, '2026-06-10 06:10:45', '2026-06-10 06:10:45');
INSERT INTO `learning_progress` (`id`, `user_id`, `course_id`, `is_completed`, `progress_percentage`, `total_learning_time`, `last_position_seconds`, `completed_at`, `created_at`, `updated_at`) VALUES (35, 11, 13, 0, 16.81, 128, 0, NULL, '2026-06-10 08:31:24', '2026-06-11 01:34:54');
INSERT INTO `learning_progress` (`id`, `user_id`, `course_id`, `is_completed`, `progress_percentage`, `total_learning_time`, `last_position_seconds`, `completed_at`, `created_at`, `updated_at`) VALUES (36, 4, 12, 0, 100.00, 60, 0, NULL, '2026-06-10 10:28:21', '2026-06-10 10:29:15');
INSERT INTO `learning_progress` (`id`, `user_id`, `course_id`, `is_completed`, `progress_percentage`, `total_learning_time`, `last_position_seconds`, `completed_at`, `created_at`, `updated_at`) VALUES (37, 11, 16, 0, 33.33, 10, 0, NULL, '2026-06-10 14:55:23', '2026-06-10 14:55:23');
INSERT INTO `learning_progress` (`id`, `user_id`, `course_id`, `is_completed`, `progress_percentage`, `total_learning_time`, `last_position_seconds`, `completed_at`, `created_at`, `updated_at`) VALUES (38, 8, 15, 0, 100.00, 2870, 0, NULL, '2026-06-10 14:56:27', '2026-06-10 15:48:08');
INSERT INTO `learning_progress` (`id`, `user_id`, `course_id`, `is_completed`, `progress_percentage`, `total_learning_time`, `last_position_seconds`, `completed_at`, `created_at`, `updated_at`) VALUES (39, 13, 11, 1, 100.00, 1473, 0, '2026-06-11 01:56:52', '2026-06-11 01:40:47', '2026-06-11 01:56:52');
COMMIT;

-- ----------------------------
-- Table structure for mentor_student
-- ----------------------------
DROP TABLE IF EXISTS `mentor_student`;
CREATE TABLE `mentor_student` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `mentor_id` bigint unsigned NOT NULL COMMENT '导师用户ID',
  `student_id` bigint unsigned NOT NULL COMMENT '学员用户ID',
  `status` enum('active','inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active' COMMENT '状态：active=活跃, inactive=停用',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `mentor_student_mentor_id_student_id_unique` (`mentor_id`,`student_id`),
  KEY `mentor_student_student_id_index` (`student_id`),
  CONSTRAINT `mentor_student_mentor_id_foreign` FOREIGN KEY (`mentor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mentor_student_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='导师学员绑定表：多对多关系';

-- ----------------------------
-- Records of mentor_student
-- ----------------------------
BEGIN;
INSERT INTO `mentor_student` (`id`, `mentor_id`, `student_id`, `status`, `created_at`, `updated_at`) VALUES (2, 7, 8, 'active', '2026-06-09 02:21:46', '2026-06-09 02:21:46');
INSERT INTO `mentor_student` (`id`, `mentor_id`, `student_id`, `status`, `created_at`, `updated_at`) VALUES (3, 7, 9, 'active', '2026-06-09 02:22:03', '2026-06-09 02:22:03');
INSERT INTO `mentor_student` (`id`, `mentor_id`, `student_id`, `status`, `created_at`, `updated_at`) VALUES (4, 7, 10, 'active', '2026-06-09 02:29:36', '2026-06-09 02:29:36');
INSERT INTO `mentor_student` (`id`, `mentor_id`, `student_id`, `status`, `created_at`, `updated_at`) VALUES (6, 7, 11, 'active', '2026-06-09 03:24:25', '2026-06-09 03:24:25');
COMMIT;

-- ----------------------------
-- Table structure for migrations
-- ----------------------------
DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '迁移文件名',
  `batch` int NOT NULL COMMENT '批次号',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Laravel 迁移记录表';

-- ----------------------------
-- Records of migrations
-- ----------------------------
BEGIN;
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28, '2024_01_10_000001_add_fill_blank_to_questions_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29, '2024_01_10_000002_update_exam_records_status_enum', 2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30, '2024_01_10_000004_create_exam_cheats_table', 3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31, '2024_01_11_000001_create_exam_courses_table', 4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32, '2024_01_11_000002_migrate_exam_courses_data', 5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33, '2024_01_11_000003_remove_course_id_from_exams', 6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34, '2026_06_12_000001_migrate_files_to_oss', 7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35, '2026_06_09_000001_add_table_comments', 8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36, '2026_06_12_000003_add_missing_table_comments', 9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37, '2026_06_08_031218_add_type_to_attachments_table', 10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38, '0001_01_01_000000_create_users_table', 11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39, '0001_01_01_000001_create_cache_table', 11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40, '0001_01_01_000002_create_jobs_table', 11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41, '0001_01_01_000003_create_personal_access_tokens_table', 11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42, '2024_01_01_000001_create_mentor_student_table', 11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43, '2024_01_01_000002_create_categories_table', 11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44, '2024_01_01_000003_create_courses_table', 11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45, '2024_01_01_000004_create_attachments_table', 11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46, '2024_01_01_000005_create_learning_progress_table', 11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47, '2024_01_01_000006_create_exams_table', 11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48, '2024_01_01_000007_create_questions_table', 11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49, '2024_01_01_000008_create_exam_records_table', 11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50, '2024_01_01_000009_create_audit_logs_table', 11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51, '2024_01_02_000001_create_series_table', 11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52, '2024_01_02_000002_simplify_categories_table', 12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53, '2024_01_02_000003_add_series_id_to_courses_table', 12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54, '2024_01_03_000001_add_upload_fields_to_courses_table', 12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55, '2024_01_04_000001_add_assigned_to_to_exam_records_table', 12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56, '2024_01_05_000001_add_mentor_id_to_courses_table', 12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57, '2024_01_06_000001_create_settings_table', 12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58, '2024_01_07_000001_add_parent_id_to_categories_table', 12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59, '2026_06_08_202140_create_notifications_table', 12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60, '2026_06_09_000002_remove_type_from_attachments_table', 12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (61, '2026_06_09_000003_create_comments_table', 13);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62, '2026_06_09_000004_create_comment_likes_table', 13);
COMMIT;

-- ----------------------------
-- Table structure for notifications
-- ----------------------------
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '主键ID',
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '通知类型',
  `notifiable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '关联对象类型',
  `notifiable_id` bigint unsigned NOT NULL COMMENT '关联对象ID',
  `data` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '通知数据（JSON格式）',
  `read_at` timestamp NULL DEFAULT NULL COMMENT '已读时间',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统通知表';

-- ----------------------------
-- Records of notifications
-- ----------------------------
BEGIN;
INSERT INTO `notifications` (`id`, `type`, `notifiable_type`, `notifiable_id`, `data`, `read_at`, `created_at`, `updated_at`) VALUES ('6dea0051-75f5-4eb8-b866-d95b634e3cda', 'App\\Notifications\\CourseCompletedNotification', 'App\\Models\\User', 13, '{\"type\":\"course_completed\",\"course_id\":11,\"course_title\":\"2026_06_09_\\u8fde\\u63a5\\u5668\\u4ea7\\u54c1\\u5bfc\\u8bba\\uff08\\u4e00\\uff09\",\"series_id\":24,\"message\":\"\\u60a8\\u5df2\\u5b8c\\u6210\\u8bfe\\u7a0b\\u300a2026_06_09_\\u8fde\\u63a5\\u5668\\u4ea7\\u54c1\\u5bfc\\u8bba\\uff08\\u4e00\\uff09\\u300b\\uff0c\\u53ef\\u4ee5\\u53c2\\u52a0\\u8003\\u8bd5\\u4e86\"}', NULL, '2026-06-11 01:56:52', '2026-06-11 01:56:52');
INSERT INTO `notifications` (`id`, `type`, `notifiable_type`, `notifiable_id`, `data`, `read_at`, `created_at`, `updated_at`) VALUES ('7aee6601-0f82-4c3c-a60b-1fa6038d4602', 'App\\Notifications\\ExamRejectedNotification', 'App\\Models\\User', 11, '{\"type\":\"exam_rejected\",\"exam_id\":5,\"exam_record_id\":11,\"exam_title\":\"\\u8fde\\u63a5\\u5668\\u5bfc\\u8bba\\u8bd5\\u9898\",\"reason\":\"\\u91cd\\u8003\",\"message\":\"\\u60a8\\u7684\\u8bd5\\u5377\\u300a\\u8fde\\u63a5\\u5668\\u5bfc\\u8bba\\u8bd5\\u9898\\u300b\\u5df2\\u88ab\\u9a73\\u56de\\uff0c\\u8bf7\\u8865\\u5145\\u56de\\u7b54\\u540e\\u91cd\\u65b0\\u63d0\\u4ea4\"}', '2026-06-11 00:55:46', '2026-06-11 00:41:09', '2026-06-11 00:55:46');
INSERT INTO `notifications` (`id`, `type`, `notifiable_type`, `notifiable_id`, `data`, `read_at`, `created_at`, `updated_at`) VALUES ('85c1314a-4232-40fe-b5d9-cce3d606561b', 'App\\Notifications\\MentionNotification', 'App\\Models\\User', 7, '{\"type\":\"mention\",\"comment_id\":1,\"series_id\":24,\"series_name\":\"\\u4ea7\\u54c1\\u901a\\u8bc6\",\"sender_id\":11,\"sender_name\":\"\\u674e\\u540c\\u5b66\",\"content\":\"@\\u5f20\\u5bfc\\u5e08 \\u5bfc\\u5e08\\u6d4b\\u8bd5\\u8bfe\\u7a0b\",\"message\":\"\\u674e\\u540c\\u5b66 \\u5728\\u8bc4\\u8bba\\u4e2d\\u63d0\\u5230\\u4e86\\u4f60\"}', '2026-06-09 15:06:59', '2026-06-09 15:04:15', '2026-06-09 15:06:59');
INSERT INTO `notifications` (`id`, `type`, `notifiable_type`, `notifiable_id`, `data`, `read_at`, `created_at`, `updated_at`) VALUES ('e86aa39f-44b1-4bc3-ab46-d8f42155374e', 'App\\Notifications\\MentionNotification', 'App\\Models\\User', 7, '{\"type\":\"mention\",\"comment_id\":2,\"series_id\":24,\"series_name\":\"\\u4ea7\\u54c1\\u901a\\u8bc6\",\"sender_id\":11,\"sender_name\":\"\\u674e\\u540c\\u5b66\",\"content\":\"@\\u5f20\\u5bfc\\u5e08\",\"message\":\"\\u674e\\u540c\\u5b66 \\u5728\\u8bc4\\u8bba\\u4e2d\\u63d0\\u5230\\u4e86\\u4f60\"}', '2026-06-10 17:15:33', '2026-06-09 20:59:24', '2026-06-10 17:15:33');
COMMIT;

-- ----------------------------
-- Table structure for password_reset_tokens
-- ----------------------------
DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '邮箱地址',
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '重置令牌',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Laravel 密码重置表';

-- ----------------------------
-- Records of password_reset_tokens
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for personal_access_tokens
-- ----------------------------
DROP TABLE IF EXISTS `personal_access_tokens`;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '令牌所属类型',
  `tokenable_id` bigint unsigned NOT NULL COMMENT '令牌所属ID',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '令牌名称',
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '令牌哈希',
  `abilities` text COLLATE utf8mb4_unicode_ci COMMENT '权限列表',
  `last_used_at` timestamp NULL DEFAULT NULL COMMENT '最后使用时间',
  `expires_at` timestamp NULL DEFAULT NULL COMMENT '过期时间',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB AUTO_INCREMENT=215 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Laravel 个人访问令牌表';

-- ----------------------------
-- Records of personal_access_tokens
-- ----------------------------
BEGIN;
INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES (156, 'App\\Models\\User', 12, 'auth-token', 'fbe882d7de623e09147c62368df63f6f2f4d6aade217181d49a6efef1b4dd00b', '[\"*\"]', '2026-06-10 01:13:42', NULL, '2026-06-10 01:13:42', '2026-06-10 01:13:42');
INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES (162, 'App\\Models\\User', 12, 'auth-token', '5a8ed361740680d6a92c848d929a19e4278866c75f86a03bffdf0ade045d3780', '[\"*\"]', '2026-06-10 03:17:31', NULL, '2026-06-10 02:01:51', '2026-06-10 03:17:31');
INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES (181, 'App\\Models\\User', 11, 'auth-token', 'c657ac4ac30d8645a4741e68a9ff6975ad6dfe2b18f4679f212d75aa40337f5d', '[\"*\"]', '2026-06-10 10:38:26', NULL, '2026-06-10 10:37:35', '2026-06-10 10:38:26');
INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES (182, 'App\\Models\\User', 11, 'auth-token', 'ebfd89a60f0cc05960e631c082f265436f79a97af078fb14c5ee7843eef47e9f', '[\"*\"]', '2026-06-10 10:44:19', NULL, '2026-06-10 10:41:47', '2026-06-10 10:44:19');
INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES (193, 'App\\Models\\User', 4, 'auth-token', '7d39f9acb7267216e474f929e29a37e6847aaa35a2f6742b2a065e07c6e2aca0', '[\"*\"]', '2026-06-10 22:40:35', NULL, '2026-06-10 18:03:32', '2026-06-10 22:40:35');
INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES (194, 'App\\Models\\User', 4, 'auth-token', 'eabbdd8e640953d55de67888df349cab226ac2c681dfd57c3cfae05d43e9412b', '[\"*\"]', '2026-06-10 18:58:19', NULL, '2026-06-10 18:55:35', '2026-06-10 18:58:19');
INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES (212, 'App\\Models\\User', 4, 'auth-token', '137bb7782049689ad1cef847671b39d6ed5e894c6bf12763dff29666cb050393', '[\"*\"]', '2026-06-11 03:51:01', NULL, '2026-06-11 02:10:33', '2026-06-11 03:51:01');
INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES (214, 'App\\Models\\User', 12, 'auth-token', 'e6063853b2bd9ba59067496a33f6f1011106a4448ae7edb25d375f046ff99334', '[\"*\"]', '2026-06-11 03:20:11', NULL, '2026-06-11 02:58:11', '2026-06-11 03:20:11');
COMMIT;

-- ----------------------------
-- Table structure for questions
-- ----------------------------
DROP TABLE IF EXISTS `questions`;
CREATE TABLE `questions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `exam_id` bigint unsigned NOT NULL COMMENT '所属考试ID',
  `course_id` bigint unsigned DEFAULT NULL COMMENT '所属课程ID',
  `type` enum('single','multiple','truefalse','short_answer','fill_blank') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '题目类型：single=单选, multiple=多选, truefalse=判断, short_answer=简答, fill_blank=填空',
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '题干',
  `options` json DEFAULT NULL COMMENT '选项',
  `correct_answer` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '正确答案',
  `score` decimal(5,2) NOT NULL COMMENT '分值',
  `sort_order` int NOT NULL DEFAULT '0' COMMENT '排序权重',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `questions_exam_id_index` (`exam_id`),
  KEY `questions_course_id_index` (`course_id`),
  KEY `questions_type_index` (`type`),
  CONSTRAINT `questions_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `questions_exam_id_foreign` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='题目表：支持单选、多选、判断、简答题型';

-- ----------------------------
-- Records of questions
-- ----------------------------
BEGIN;
INSERT INTO `questions` (`id`, `exam_id`, `course_id`, `type`, `content`, `options`, `correct_answer`, `score`, `sort_order`, `created_at`, `updated_at`) VALUES (7, 5, 11, 'short_answer', '什么是连接器？', NULL, '连接两个有源器件的器件，传输电流、数据或信号', 10.00, 0, '2026-06-09 09:17:57', '2026-06-11 02:55:59');
INSERT INTO `questions` (`id`, `exam_id`, `course_id`, `type`, `content`, `options`, `correct_answer`, `score`, `sort_order`, `created_at`, `updated_at`) VALUES (8, 5, 13, 'short_answer', '连接器的优点有哪些？（4个）', NULL, '改善生产进程 、易于修理 、便于升级、 设计的灵活性', 10.00, 0, '2026-06-09 09:18:30', '2026-06-09 09:18:30');
INSERT INTO `questions` (`id`, `exam_id`, `course_id`, `type`, `content`, `options`, `correct_answer`, `score`, `sort_order`, `created_at`, `updated_at`) VALUES (9, 5, 13, 'short_answer', '全球主要连接器厂家，列举3个', NULL, '泰科、莫仕、安费诺', 10.00, 0, '2026-06-09 09:20:14', '2026-06-09 09:20:14');
INSERT INTO `questions` (`id`, `exam_id`, `course_id`, `type`, `content`, `options`, `correct_answer`, `score`, `sort_order`, `created_at`, `updated_at`) VALUES (10, 5, 15, 'short_answer', '连接器的主要应用行业有哪些？（4个）', NULL, '汽车、通讯、消费电子、工业、军工、轨交', 10.00, 0, '2026-06-09 09:21:00', '2026-06-11 02:56:07');
INSERT INTO `questions` (`id`, `exam_id`, `course_id`, `type`, `content`, `options`, `correct_answer`, `score`, `sort_order`, `created_at`, `updated_at`) VALUES (11, 5, 15, 'short_answer', '连接器主要由哪三个部分组成？连接器的配件有哪些？（不少于2种）', NULL, '接触件/中心针 绝缘体 外壳  \n螺丝、螺母、垫片、防尘帽', 10.00, 0, '2026-06-09 09:21:45', '2026-06-09 09:21:45');
INSERT INTO `questions` (`id`, `exam_id`, `course_id`, `type`, `content`, `options`, `correct_answer`, `score`, `sort_order`, `created_at`, `updated_at`) VALUES (12, 5, 11, 'short_answer', '连接器的主要性能参数有哪三种？每种列举出两个参数值。', NULL, '机械性能（使用寿命、插拔力） 电气性能（额定电流、耐受电压、插入损耗）\n环境性能（耐温、耐湿、耐盐雾、振动和冲击）', 10.00, 0, '2026-06-09 09:22:18', '2026-06-09 09:22:18');
INSERT INTO `questions` (`id`, `exam_id`, `course_id`, `type`, `content`, `options`, `correct_answer`, `score`, `sort_order`, `created_at`, `updated_at`) VALUES (13, 5, 15, 'short_answer', '连接器的四种基本属性有哪些？', NULL, '公母、直弯、针数、对接方式', 10.00, 0, '2026-06-09 09:22:45', '2026-06-09 09:22:45');
INSERT INTO `questions` (`id`, `exam_id`, `course_id`, `type`, `content`, `options`, `correct_answer`, `score`, `sort_order`, `created_at`, `updated_at`) VALUES (14, 5, 15, 'short_answer', '连接器的安装方式有哪2种？（请列举大类及其中的小类，小类2个）', NULL, '面板安装（穿墙、法兰）接线（模具成型、组装）', 10.00, 0, '2026-06-09 09:23:45', '2026-06-09 09:23:45');
INSERT INTO `questions` (`id`, `exam_id`, `course_id`, `type`, `content`, `options`, `correct_answer`, `score`, `sort_order`, `created_at`, `updated_at`) VALUES (15, 5, 15, 'fill_blank', '请将以下产品专有名词翻译成英文。\n\n连接器 （ ）     转接头  （ ）       插头（ ）          插座（ ）          公针 （） 母针（ ）  绝缘体（ ）   安装方式（ ）   穿墙安装（ ）   法兰安装（ ）', NULL, '[\"连接器（Connector）\",\"转接头（adapter）\",\"插头（plug）\",\"插座（socket）\",\"公针（male pin\\/male contact）\",\"母针（female pin\\/female contact）\",\"绝缘体（insulator）\",\"安装方式（mounting feature）\",\"穿墙安装（bulkhead）\",\"法兰安装（flange mount）\"]', 10.00, 0, '2026-06-09 09:25:33', '2026-06-11 03:36:15');
INSERT INTO `questions` (`id`, `exam_id`, `course_id`, `type`, `content`, `options`, `correct_answer`, `score`, `sort_order`, `created_at`, `updated_at`) VALUES (16, 5, 15, 'fill_blank', '请区分以下连接器哪些是前锁板，哪些是后锁板？\n\n![图片](/storage/courses/2026/06/1781002485_SWhoeAfvVD.webp)（ ）\n![图片](/storage/courses/2026/06/1781002496_evzX1NMy49.webp)（ ）\n![图片](/storage/courses/2026/06/1781002504_21LajDCa7p.webp)（ ）\n![图片](/storage/courses/2026/06/1781002514_C0gmL6dqPI.webp)（ ）', NULL, '[\"\\u524d\\u9501\",\"\\u524d\\u9501\",\"\\u540e\\u9501\",\"\\u540e\\u9501\"]', 10.00, 0, '2026-06-09 10:55:44', '2026-06-10 05:50:00');
COMMIT;

-- ----------------------------
-- Table structure for series
-- ----------------------------
DROP TABLE IF EXISTS `series`;
CREATE TABLE `series` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '系列名称',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT '系列简介',
  `category_id` bigint unsigned NOT NULL COMMENT '所属分类ID',
  `cover_image` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '封面图',
  `sort_order` int NOT NULL DEFAULT '0' COMMENT '排序权重',
  `status` enum('draft','published','archived') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft' COMMENT '状态：draft=草稿, published=已发布, archived=已归档',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `series_category_id_index` (`category_id`),
  KEY `series_status_index` (`status`),
  KEY `series_category_id_status_sort_order_index` (`category_id`,`status`,`sort_order`),
  CONSTRAINT `series_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='培训系列表：关联分类，包含多个课程';

-- ----------------------------
-- Records of series
-- ----------------------------
BEGIN;
INSERT INTO `series` (`id`, `name`, `description`, `category_id`, `cover_image`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES (3, '企业与文化融入', NULL, 16, NULL, 1, 'published', '2026-06-09 05:54:40', '2026-06-11 02:11:44');
INSERT INTO `series` (`id`, `name`, `description`, `category_id`, `cover_image`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES (5, '职业素养', NULL, 18, NULL, 0, 'published', '2026-06-09 07:15:15', '2026-06-09 07:15:17');
INSERT INTO `series` (`id`, `name`, `description`, `category_id`, `cover_image`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES (6, '销售素养', NULL, 19, NULL, 0, 'published', '2026-06-09 07:21:55', '2026-06-09 07:38:26');
INSERT INTO `series` (`id`, `name`, `description`, `category_id`, `cover_image`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES (7, '销售的底层逻辑', NULL, 19, NULL, 0, 'published', '2026-06-09 07:22:08', '2026-06-10 02:07:35');
INSERT INTO `series` (`id`, `name`, `description`, `category_id`, `cover_image`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES (8, '销售过程管理', NULL, 19, NULL, 0, 'published', '2026-06-09 07:22:24', '2026-06-09 07:38:24');
INSERT INTO `series` (`id`, `name`, `description`, `category_id`, `cover_image`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES (9, '业务流程全景', NULL, 20, NULL, 0, 'published', '2026-06-09 07:34:46', '2026-06-09 07:38:22');
INSERT INTO `series` (`id`, `name`, `description`, `category_id`, `cover_image`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES (10, '数字化系统实操', NULL, 20, NULL, 0, 'published', '2026-06-09 07:35:00', '2026-06-09 07:38:21');
INSERT INTO `series` (`id`, `name`, `description`, `category_id`, `cover_image`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES (11, '客户获取方式及渠道（通用-我们公司适用）', NULL, 21, NULL, 0, 'published', '2026-06-09 07:35:17', '2026-06-09 07:38:20');
INSERT INTO `series` (`id`, `name`, `description`, `category_id`, `cover_image`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES (12, '询盘甄别与转化', NULL, 21, NULL, 0, 'published', '2026-06-09 07:35:29', '2026-06-09 07:38:19');
INSERT INTO `series` (`id`, `name`, `description`, `category_id`, `cover_image`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES (13, '需求深挖与谈判', NULL, 21, NULL, 0, 'published', '2026-06-09 07:35:40', '2026-06-09 07:38:18');
INSERT INTO `series` (`id`, `name`, `description`, `category_id`, `cover_image`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES (14, '样品确认流程', NULL, 21, NULL, 0, 'published', '2026-06-09 07:35:52', '2026-06-09 07:38:17');
INSERT INTO `series` (`id`, `name`, `description`, `category_id`, `cover_image`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES (15, '订单履约与风控', NULL, 21, NULL, 0, 'published', '2026-06-09 07:36:04', '2026-06-09 07:38:16');
INSERT INTO `series` (`id`, `name`, `description`, `category_id`, `cover_image`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES (16, '交付与售后闭环', NULL, 21, NULL, 0, 'published', '2026-06-09 07:36:21', '2026-06-09 07:38:16');
INSERT INTO `series` (`id`, `name`, `description`, `category_id`, `cover_image`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES (17, '客户管理', NULL, 21, NULL, 0, 'published', '2026-06-09 07:36:35', '2026-06-09 07:38:15');
INSERT INTO `series` (`id`, `name`, `description`, `category_id`, `cover_image`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES (18, '绩效', NULL, 16, NULL, 4, 'published', '2026-06-09 07:36:47', '2026-06-10 02:06:50');
INSERT INTO `series` (`id`, `name`, `description`, `category_id`, `cover_image`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES (19, '转正', NULL, 16, NULL, 3, 'published', '2026-06-09 07:37:06', '2026-06-11 02:12:52');
INSERT INTO `series` (`id`, `name`, `description`, `category_id`, `cover_image`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES (20, '秋风', NULL, 22, NULL, 0, 'published', '2026-06-09 07:37:20', '2026-06-09 07:38:13');
INSERT INTO `series` (`id`, `name`, `description`, `category_id`, `cover_image`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES (21, '王牌', NULL, 22, NULL, 0, 'published', '2026-06-09 07:37:32', '2026-06-09 07:38:12');
INSERT INTO `series` (`id`, `name`, `description`, `category_id`, `cover_image`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES (22, '飞跃', NULL, 22, NULL, 0, 'published', '2026-06-09 07:37:55', '2026-06-09 07:38:12');
INSERT INTO `series` (`id`, `name`, `description`, `category_id`, `cover_image`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES (23, '女神', NULL, 22, NULL, 0, 'published', '2026-06-09 07:38:09', '2026-06-09 07:56:41');
INSERT INTO `series` (`id`, `name`, `description`, `category_id`, `cover_image`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES (24, '产品基础知识', NULL, 16, NULL, 2, 'published', '2026-06-09 08:08:41', '2026-06-11 02:12:05');
COMMIT;

-- ----------------------------
-- Table structure for sessions
-- ----------------------------
DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '会话ID',
  `user_id` bigint unsigned DEFAULT NULL COMMENT '用户ID',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'IP地址',
  `user_agent` text COLLATE utf8mb4_unicode_ci COMMENT '用户代理',
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '会话数据',
  `last_activity` int NOT NULL COMMENT '最后活动时间',
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Laravel 会话表';

-- ----------------------------
-- Records of sessions
-- ----------------------------
BEGIN;
INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES ('DkGhkdSlEXLOg26YVZxROerQy1fdgOFwsEbU9RGl', 4, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'eyJfdG9rZW4iOiJDTXNFS0pSaWNUamJUWWdvNGRKU2lpa29SR0g5QmMwblRIOWFZbEhCIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2xvY2FsaG9zdDo4MDAwXC9hcGlcL2hvbWVwYWdlIiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19', 1780884759);
INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES ('jSsF4XNADJxB7tTG2elvJSve6HIIoLHTo6RfEpqy', NULL, '127.0.0.1', 'curl/8.7.1', 'eyJfdG9rZW4iOiJ3bnZEWE5QbzlRUDZQbmVmaERoTDc1ejRkaEM5RURwT3JVZW93OHB2IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwIiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19', 1781052868);
INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES ('vYNhNltshfjbPCCigaff90Z78pgisAGRgLWiv0Ht', 4, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'eyJfdG9rZW4iOiJOd3FXRm1sbWxNdDlQQ2JTa3ZOTXo1clc2WEp0SkEwZkRvd3ZFRDVuIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2xvY2FsaG9zdDo4MDAwXC9hcGlcL21lbnRvclwvcGVuZGluZy1yZXZpZXdzIiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19', 1780884775);
INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES ('zOtMyaknpXPpBsgB1TuQdNCD10Vfwh5Zt05OCEsD', 4, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'eyJfdG9rZW4iOiJnQlQxcnlBVXZ6azdQYWNodkNiWm5xVkEyNExRUHNEMVFBZkhuTHdkIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2xvY2FsaG9zdDo4MDAwXC9hcGlcL2V4YW1zIiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19', 1780884772);
COMMIT;

-- ----------------------------
-- Table structure for settings
-- ----------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '配置键',
  `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '配置值',
  `group` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general' COMMENT '配置分组',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统设置表：存储键值对配置';

-- ----------------------------
-- Records of settings
-- ----------------------------
BEGIN;
INSERT INTO `settings` (`id`, `key`, `value`, `group`, `created_at`, `updated_at`) VALUES (1, 'system_name', 'Renhotec Academy', 'general', '2026-06-08 01:02:01', '2026-06-08 01:02:01');
INSERT INTO `settings` (`id`, `key`, `value`, `group`, `created_at`, `updated_at`) VALUES (2, 'system_subtitle', '员工培训与考试系统', 'general', '2026-06-08 01:02:01', '2026-06-08 01:02:01');
INSERT INTO `settings` (`id`, `key`, `value`, `group`, `created_at`, `updated_at`) VALUES (3, 'system_logo', 'academy/dev/logos/1780902397_TKU2GthjVg.png', 'general', '2026-06-08 01:02:01', '2026-06-10 03:45:43');
COMMIT;

-- ----------------------------
-- Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '用户姓名',
  `employee_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '工号',
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '邮箱地址',
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '手机号码',
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '登录密码（哈希值）',
  `department` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '部门',
  `position` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '岗位',
  `role` enum('student','mentor','admin') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'student' COMMENT '角色：student=学员, mentor=导师, admin=管理员',
  `status` enum('active','inactive','locked') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active' COMMENT '状态：active=正常, inactive=停用, locked=锁定',
  `hire_date` date DEFAULT NULL COMMENT '入职日期',
  `trial_end_date` date DEFAULT NULL COMMENT '试用期截止日',
  `email_verified_at` timestamp NULL DEFAULT NULL COMMENT '邮箱验证时间',
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '记住登录令牌',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_employee_no_unique` (`employee_no`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_role_index` (`role`),
  KEY `users_status_index` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户表：存储员工信息、角色、状态等';

-- ----------------------------
-- Records of users
-- ----------------------------
BEGIN;
INSERT INTO `users` (`id`, `name`, `employee_no`, `email`, `phone`, `password`, `department`, `position`, `role`, `status`, `hire_date`, `trial_end_date`, `email_verified_at`, `remember_token`, `created_at`, `updated_at`) VALUES (4, '系统管理员', 'ADMIN001', 'admin@renhotec.com', '13800000001', '$2y$12$U7tBZ.r1G9SeZPqipoBvZOlY8MdO3Q3p9I1TNxijN/e1UhLXXkSqW', '技术部', '系统管理员', 'admin', 'active', '2024-01-01', NULL, NULL, NULL, '2026-06-08 01:02:00', '2026-06-08 01:02:00');
INSERT INTO `users` (`id`, `name`, `employee_no`, `email`, `phone`, `password`, `department`, `position`, `role`, `status`, `hire_date`, `trial_end_date`, `email_verified_at`, `remember_token`, `created_at`, `updated_at`) VALUES (7, '张导师', 'MEN001', 'mentor@test.com', NULL, '$2y$12$UkGR2ptk3HyIg.l04zTayulAwz4M8264.R.IVoT2uJQ.yy9fFdtqW', NULL, NULL, 'mentor', 'active', '2023-06-01', NULL, NULL, NULL, '2026-06-08 10:18:24', '2026-06-08 06:19:58');
INSERT INTO `users` (`id`, `name`, `employee_no`, `email`, `phone`, `password`, `department`, `position`, `role`, `status`, `hire_date`, `trial_end_date`, `email_verified_at`, `remember_token`, `created_at`, `updated_at`) VALUES (8, '李学员', 'STU001', 'student@test.com', NULL, '$2y$12$HV8jWbzej8eqay4S5gY9AOLnN9EvRl/.XXJYshSvBRsyStkJFEsyW', NULL, NULL, 'student', 'active', '2026-06-09', '2026-06-30', NULL, NULL, '2026-06-08 10:18:24', '2026-06-09 02:21:46');
INSERT INTO `users` (`id`, `name`, `employee_no`, `email`, `phone`, `password`, `department`, `position`, `role`, `status`, `hire_date`, `trial_end_date`, `email_verified_at`, `remember_token`, `created_at`, `updated_at`) VALUES (9, 'ceshixueyuan', 'tb0001', 'cjay95997@gmail.com', NULL, '$2y$12$3Kw6LCRO4xsVIkzC/f136u8pA7g5kK7u7Zpq/DneXu5Cg9oCN9bSS', NULL, NULL, 'student', 'active', '2026-06-08', '2026-06-19', NULL, NULL, '2026-06-08 06:02:34', '2026-06-08 06:02:34');
INSERT INTO `users` (`id`, `name`, `employee_no`, `email`, `phone`, `password`, `department`, `position`, `role`, `status`, `hire_date`, `trial_end_date`, `email_verified_at`, `remember_token`, `created_at`, `updated_at`) VALUES (10, '张同学', 'test001', 'cjay95997@qq.com', '1732132133', '$2y$12$dxM02juHt88L0Kofg.CknugHTbR0Yk12o5Y0Rcqko3IU/6pJsyng6', '技术部', NULL, 'student', 'active', '2026-06-02', '2026-06-08', NULL, NULL, '2026-06-09 02:29:36', '2026-06-09 02:29:36');
INSERT INTO `users` (`id`, `name`, `employee_no`, `email`, `phone`, `password`, `department`, `position`, `role`, `status`, `hire_date`, `trial_end_date`, `email_verified_at`, `remember_token`, `created_at`, `updated_at`) VALUES (11, '李同学', 'test002', NULL, '12313131', '$2y$12$KtfkOj2aoVQ4SseTlvpS.OrJ2KOOGvbz4qe0nKTbxiB8/teoh2pLG', NULL, NULL, 'student', 'active', '2026-06-02', '2026-06-25', NULL, NULL, '2026-06-09 02:30:14', '2026-06-09 03:24:25');
INSERT INTO `users` (`id`, `name`, `employee_no`, `email`, `phone`, `password`, `department`, `position`, `role`, `status`, `hire_date`, `trial_end_date`, `email_verified_at`, `remember_token`, `created_at`, `updated_at`) VALUES (12, '熊曼', 'Renhotec001', NULL, NULL, '$2y$12$BTR90AkIYT93MXR4aAMr9eKLilDsE80C5cOeGt.d9akVo/3kVcali', '人力行政部', '人力专员', 'admin', 'active', NULL, NULL, NULL, NULL, '2026-06-10 00:31:19', '2026-06-10 00:31:19');
INSERT INTO `users` (`id`, `name`, `employee_no`, `email`, `phone`, `password`, `department`, `position`, `role`, `status`, `hire_date`, `trial_end_date`, `email_verified_at`, `remember_token`, `created_at`, `updated_at`) VALUES (13, '测试1号', 'test032', NULL, NULL, '$2y$12$JRFRht5wk2ep7RfDPnM8H.fAK43B.hrO2MF2DZQR46XsB6fsZvNe.', NULL, NULL, 'student', 'active', NULL, NULL, NULL, NULL, '2026-06-11 01:40:08', '2026-06-11 01:40:08');
COMMIT;

SET FOREIGN_KEY_CHECKS = 1;
