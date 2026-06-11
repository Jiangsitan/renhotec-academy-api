# Renhotec Academy - 后端 API

## 项目概述

Renhotec Academy 后端 API 是基于 Laravel 13 构建的 RESTful API 服务，为前端应用提供数据接口。

## 技术栈

| 技术 | 版本 | 说明 |
|------|------|------|
| PHP | 8.3 | 运行环境 |
| Laravel | 13.x | Web 框架 |
| MySQL | 8.0 | 数据库 |
| Redis | 7.x | 缓存 |
| Laravel Sanctum | 4.3 | API 认证 |
| PHPUnit | 12.x | 单元测试 |

## 项目结构

```
academy_api/
├── app/
│   ├── Enums/                  # 枚举类
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/           # API 控制器
│   │   ├── Middleware/        # 中间件
│   │   └── Requests/          # 请求验证
│   ├── Models/                # 数据模型
│   ├── Notifications/         # 通知类
│   └── Services/              # 业务服务
├── config/                    # 配置文件
├── database/
│   ├── migrations/            # 数据库迁移
│   └── seeders/               # 数据填充
├── public/
│   └── storage/               # 公开存储（附件、视频）
├── routes/
│   └── api.php                # API 路由
├── storage/                   # 私有存储
├── tests/                     # 测试文件
│   ├── Feature/               # 功能测试
│   └── Unit/                  # 单元测试
├── .env.example               # 环境变量示例
├── composer.json              # PHP 依赖
└── artisan                    # CLI 工具
```

## API 控制器

| 控制器 | 说明 |
|--------|------|
| AuthController | 用户认证（登录、登出） |
| AdminController | 管理后台（课程、考试、用户、分类、系列） |
| CourseController | 课程详情 |
| SeriesController | 系列详情 |
| ExamController | 考试列表和详情 |
| ExamRecordController | 考试记录和提交 |
| MentorController | 导师批改 |
| LearningProgressController | 学习进度同步 |
| CommentController | 评论管理 |
| NotificationController | 通知管理 |
| FileUploadController | 文件上传 |
| FileServeController | 文件服务 |
| SettingController | 系统设置 |
| HomepageController | 首页数据 |

## 数据模型

| 模型 | 说明 | 关系 |
|------|------|------|
| User | 用户 | hasMany LearningProgress, ExamRecord |
| Category | 分类 | hasMany Course, Series |
| Series | 系列 | belongsTo Category, hasMany Course |
| Course | 课程 | belongsTo Category, Series |
| Exam | 考试 | belongsToMany Course, hasMany Question |
| Question | 题目 | belongsTo Exam |
| ExamRecord | 考试记录 | belongsTo User, Exam |
| LearningProgress | 学习进度 | belongsTo User, Course |
| Notification | 通知 | belongsTo User |

## 环境变量

```env
APP_NAME="Renhotec Academy"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:9000
FRONTEND_URL=http://localhost:3000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=renhotec_academy
DB_USERNAME=root
DB_PASSWORD=

REDIS_HOST=127.0.0.1
REDIS_PORT=6379

SANCTUM_STATEFUL_DOMAINS=localhost:3000,localhost:9000
```

## API 路由概览

### 认证
| 方法 | 路径 | 说明 |
|------|------|------|
| POST | `/api/login` | 用户登录 |
| POST | `/api/logout` | 用户登出 |
| GET | `/api/me` | 获取当前用户 |

### 课程
| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/courses` | 课程列表 |
| GET | `/api/courses/{id}` | 课程详情 |

### 考试
| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/exams` | 考试列表 |
| GET | `/api/exams/{id}` | 考试详情 |
| POST | `/api/exams/{id}/submit` | 提交考试 |

### 学习进度
| 方法 | 路径 | 说明 |
|------|------|------|
| POST | `/api/learning/progress/sync` | 同步进度 |
| POST | `/api/learning/progress/complete` | 标记完成 |

### 管理后台
| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/admin/courses` | 管理课程列表 |
| POST | `/api/admin/courses` | 创建课程 |
| PUT | `/api/admin/courses/{id}` | 更新课程 |
| DELETE | `/api/admin/courses/{id}` | 删除课程 |
| POST | `/api/admin/courses/import` | 批量导入课程 |
| GET | `/api/admin/exams` | 管理考试列表 |
| POST | `/api/admin/exams` | 创建考试 |
| PUT | `/api/admin/exams/{id}` | 更新考试 |
| DELETE | `/api/admin/exams/{id}` | 删除考试 |
| POST | `/api/admin/exams/import` | 批量导入考试 |
| GET | `/api/admin/users` | 用户列表 |
| POST | `/api/admin/users` | 创建用户 |
| PUT | `/api/admin/users/{id}` | 更新用户 |
| DELETE | `/api/admin/users/{id}` | 删除用户 |
| GET | `/api/admin/categories` | 分类列表 |
| POST | `/api/admin/categories` | 创建分类 |
| GET | `/api/admin/series` | 系列列表 |
| POST | `/api/admin/series` | 创建系列 |
| GET | `/api/admin/learning-progress` | 学习进度统计 |
| GET | `/api/admin/pending-reviews` | 待批改列表 |
| POST | `/api/admin/review/{id}` | 批改试卷 |
| GET | `/api/admin/settings` | 系统设置 |
| PUT | `/api/admin/settings` | 更新设置 |

> 完整 API 路由请查看 `routes/api.php`

## 常用命令

```bash
# 安装依赖
composer install

# 生成密钥
php artisan key:generate

# 运行迁移
php artisan migrate

# 填充数据
php artisan db:seed

# 清除缓存
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# 创建存储链接
php artisan storage:link

# 启动开发服务器
php artisan serve --host 0.0.0.0 --port 9000
```

## 测试

### 运行测试

```bash
# 运行所有测试
php artisan test

# 运行指定测试文件
php artisan test --filter=ExampleTest

# 运行 Feature 测试
php artisan test --testsuite=Feature

# 运行 Unit 测试
php artisan test --testsuite=Unit

# 生成测试覆盖率报告
php artisan test --coverage
```

### 测试结构

```
tests/
├── Feature/                   # 功能测试
│   ├── Auth/                 # 认证测试
│   ├── Course/               # 课程测试
│   ├── Exam/                 # 考试测试
│   └── ...
├── Unit/                     # 单元测试
│   ├── Models/               # 模型测试
│   ├── Services/             # 服务测试
│   └── ...
└── TestCase.php              # 测试基类
```

### 编写测试

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CourseTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_courses(): void
    {
        $response = $this->getJson('/api/courses');
        $response->assertStatus(200);
    }

    public function test_can_create_course(): void
    {
        $this->actingAs($this->admin);
        $response = $this->postJson('/api/admin/courses', [
            'title' => 'Test Course',
            'type' => 'video',
        ]);
        $response->assertStatus(201);
    }
}
```

### 测试数据库

测试使用独立的数据库，配置在 `phpunit.xml` 中：

```xml
<php>
    <env name="DB_DATABASE" value="renhotec_academy_test"/>
</php>
```

## 数据库迁移

```bash
# 运行迁移
php artisan migrate

# 回滚迁移
php artisan migrate:rollback

# 重置迁移
php artisan migrate:reset

# 刷新迁移
php artisan migrate:refresh --seed
```

## 部署

### 生产环境配置

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.yourdomain.com
```

## 项目打包

### 生产环境构建

```bash
# 安装依赖（排除开发依赖）
composer install --optimize-autoloader --no-dev

# 生成自动加载文件
composer dump-autoload --optimize

# 缓存配置
php artisan config:cache

# 缓存路由
php artisan route:cache

# 缓存视图
php artisan view:cache

# 缓存事件
php artisan event:cache
```

### 一键部署脚本

```bash
# 使用 composer setup 命令（自动执行初始化流程）
composer setup

# 或手动执行以下步骤
composer install --no-dev
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
```

### Docker 打包

```bash
# 构建镜像
docker build -t renhotec-academy-api .

# 运行容器
docker run -d -p 9000:9000 --name api renhotec-academy-api

# 查看日志
docker logs -f api
```

### 优化命令

```bash
# 缓存优化
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 清除缓存
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## 联系方式

- 项目负责人：Lucas Jay
- 邮箱：2434624535@qq.com
- 文档更新日期：2026-06-11
