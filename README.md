# coachtech 勤怠管理アプリ
## 環境構築
1. `git clone  git@github.com:hiyo1925fiore/Coachtech-AttendanceManagement.git`
2. Dockerを起動する
3. プロジェクト直下で、以下のコマンドを実行する
```
make init
```
## メール認証
mailtrapというツールを使用しています。  
以下のリンクから会員登録をしてください。  
https://mailtrap.io/

メールボックスのIntegrationsから 「laravel 7.x and 8.x」を選択し、  
.envファイルのMAIL_MAILERからMAIL_ENCRYPTIONまでの項目をコピー＆ペーストしてください。  
MAIL_FROM_ADDRESSは任意のメールアドレスを入力してください。
## テーブル仕様
### usersテーブル

|**カラム名**|**型**|**primary key**|**unique key**|**not null**|**foreign key**|
|---|---|---|---|---|---|
|id|bigint|○| |○| |
|name|varchar(255)| | |○| |
|email|varchar(255)| |○|○|　|
|email_verified_at|timestamp| | | | |
|password|varchar(255)| | |○| |
|remember_token|varchar(100)| | | | |
|created_at|timestamp| | | | |
|updated_at|timestamp| | | | |
|is_admin|tinyint(1)| | |○| |

### attendancesテーブル

|**カラム名**|**型**|**primary key**|**unique key**|**not null**|**foreign key**|
|---|---|---|---|---|---|
|id|bigint|○| |○| |
|user_id|bigint| | |○|users(id)|
|date|date| | |○|　|
|start_time|time| | | | |
|end_time|time| | | | |
|note|varchar(255)| | | | |
|created_at|timestamp| | | | |
|updated_at|timestamp| | | | |

### break_timesテーブル

|**カラム名**|**型**|**primary key**|**unique key**|**not null**|**foreign key**|
|---|---|---|---|---|---|
|id|bigint|○| |○| |
|attendance_id|bigint| | |○|attendances(id)|
|start_time|time| | |○| |
|end_time|time| | | | |
|created_at|timestamp| | | | |
|updated_at|timestamp| | | | |

### attendance_requestsテーブル

|**カラム名**|**型**|**primary key**|**unique key**|**not null**|**foreign key**|
|---|---|---|---|---|---|
|id|bigint|○| |○| |
|user_id|bigint| | |○|users(id)|
|attendance_id|bigint| | |○|attendances(id)|
|start_time|time| | |○| |
|end_time|time| | |○| |
|note|varchar(255)| | |○| |
|is_approved|tinyint(1)| | |○| |
|created_at|timestamp| | | | |
|updated_at|timestamp| | | | |

### break_time_requestsテーブル

|**カラム名**|**型**|**primary key**|**unique key**|**not null**|**foreign key**|
|---|---|---|---|---|---|
|id|bigint|○| |○| |
|attendance_request_id|bigint| | |○|attendance_requests(id)|
|start_time|time| | |○| |
|end_time|time| | |○| |
|created_at|timestamp| | | | |
|updated_at|timestamp| | | | |
## ER図
## テストアカウント
- name: 一般ユーザー1
- email: general1@example.com
- password: password  
- name: 一般ユーザー2
- email: general2@example.com
- password: password  
- name: 管理者ユーザー
- email: admin@example.com
- password: password
