Warning: A partial dump from a server that has GTIDs will by default include the GTIDs of all transactions, even those that changed suppressed parts of the database. If you don't want to restore GTIDs, pass --set-gtid-purged=OFF. To make a complete dump, pass --all-databases --triggers --routines --events. 
Warning: A dump from a server that has GTIDs enabled will by default include the GTIDs of all transactions, even those that were executed during its extraction and might not be represented in the dumped data. This might result in an inconsistent data dump. 
In order to ensure a consistent backup of the database, pass --single-transaction or --lock-all-tables or --source-data. 

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
SET @MYSQLDUMP_TEMP_LOG_BIN = @@SESSION.SQL_LOG_BIN;
SET @@SESSION.SQL_LOG_BIN= 0;
SET @@GLOBAL.GTID_PURGED=/*!80000 '+'*/ '782f4b7a-1468-11f1-83de-da2734913c79:1-12028';
DROP TABLE IF EXISTS `attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '缓存键',
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '缓存值',
  `expiration` bigint NOT NULL COMMENT '过期时间',
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Laravel 缓存表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '锁键',
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '锁所有者',
  `expiration` int NOT NULL COMMENT '过期时间',
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Laravel 缓存锁表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '分类名称',
  `parent_id` bigint unsigned DEFAULT NULL COMMENT '父分类ID，NULL表示一级分类',
  `sort_order` int NOT NULL DEFAULT '0' COMMENT '排序权重',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='课程分类表：支持两级分类结构';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `comment_likes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `exam_cheats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `exam_courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `exam_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `exams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `learning_progress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mentor_student`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '迁移文件名',
  `batch` int NOT NULL COMMENT '批次号',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Laravel 迁移记录表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '邮箱地址',
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '重置令牌',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Laravel 密码重置表';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `series`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;
SET @@SESSION.SQL_LOG_BIN = @MYSQLDUMP_TEMP_LOG_BIN;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

