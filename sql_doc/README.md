# 数据库表结构文档

## 项目数据库说明
本文档包含 Renhotec Academy 项目数据库的完整表结构定义。数据库使用 MySQL，包含所有表的 CREATE TABLE 语句、外键约束、索引等。

## SQL 文件
完整的表结构 SQL 脚本位于：[`database_schema.sql`](./database_schema.sql)

## 表结构概览
数据库包含以下 26 个表：

| 表名 | 说明 |
|------|------|
| attachments | 附件表 |
| audit_logs | 审计日志表 |
| cache | 缓存表 |
| cache_locks | 缓存锁表 |
| categories | 分类表 |
| comment_likes | 评论点赞表 |
| comments | 评论表 |
| courses | 课程表 |
| exam_cheats | 考试作弊记录表 |
| exam_courses | 考试课程关联表 |
| exam_records | 考试记录表 |
| exams | 考试表 |
| failed_jobs | 失败任务表 |
| job_batches | 任务批次表 |
| jobs | 任务表 |
| learning_progress | 学习进度表 |
| mentor_student | 师生关系表 |
| migrations | 迁移记录表 |
| notifications | 通知表 |
| password_reset_tokens | 密码重置令牌表 |
| personal_access_tokens | 个人访问令牌表 |
| questions | 题目表 |
| series | 系列/专栏表 |
| sessions | 会话表 |
| settings | 设置表 |
| users | 用户表 |

## 使用说明
1. **导入数据库结构**：
   ```bash
   mysql -h 127.0.0.1 -u root -p renhotec_academy < database_schema.sql
   ```

2. **查看特定表结构**：
   ```bash
   grep -A 20 "CREATE TABLE `表名`" database_schema.sql
   ```

3. **生成时间**：本文档自动生成于项目数据库结构。

## 注意事项
- SQL 文件包含完整的表定义、外键约束和索引
- 不包含数据，仅结构定义
- 适用于本地开发环境数据库重建
- 生产环境请使用迁移文件管理数据库变更