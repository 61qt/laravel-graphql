# Changelog

本文档记录 laravel-graphql 包的所有重要变更。

格式基于 [Keep a Changelog](https://keepachangelog.com/zh-CN/1.0.0/)，
并且本项目遵循 [语义化版本](https://semver.org/lang/zh-CN/)。

## [Unreleased]

### Changed (⚠️ 破坏性变更)

#### 移除 rawSql 操作符
由于存在 SQL 注入风险且实际使用场景极少，已完全移除 `rawSql` 操作符。

**影响**: 
- 如果有代码使用 `{ filter: { rawSql: "..." } }` 进行查询，需要改为在 resolver 中使用 `$query->whereRaw()` 显式构造
- 建议在 resolver 的 `beforeQuery` 或其他钩子方法中处理复杂的查询条件

**迁移示例**:
```php
// 旧方式（已移除）
{ users(filter: { rawSql: "status = 1" }) { id } }

// 新方式：在 resolver 中显式构造
class UserResolver extends Resolver
{
    protected function beforeQuery(Context $context)
    {
        $query = $this->getModelQuery();
        // 根据业务需求添加复杂的 where 条件
        $query->whereRaw('status = ?', [1]);
    }
}
```

### Fixed
- HasMany 关联查询列名歧义问题
- buildJoin 配置验证增强
- HasOneOrMany 构造函数初始化顺序调整

### Security
- 移除 rawSql 操作符，消除 SQL 注入风险

## [0.6.8] - 2025-06-16

### Added
- 增加注释，支持 Laravel 12

### Changed
- 优化代码结构和可维护性

## [0.6.7] - 2024-12-03

### Changed (⚠️ 破坏性变更)

#### ResolverTriggers Hook 签名变更
以下钩子方法的签名发生了破坏性变更，如果您的 resolver 子类重写了这些方法，需要更新方法签名：

**beforeDestroy**
```php
// v0.6.6 及之前
protected function beforeDestroy(Context $context, Model $model)

// v0.6.7 及之后
protected function beforeDestroy(Context $context, Model $model, array $input)
```

**afterDestroy**
```php
// v0.6.6 及之前
protected function afterDestroy(Model $model)

// v0.6.7 及之后
protected function afterDestroy(Model $model, array $input)
```

**说明**: 新增的 `$input` 参数包含删除操作的输入数据，便于在钩子中访问原始请求参数。

## [0.6.6] - 2024-10-30

### Added
- 增加 extraKey 的关联逻辑
- 优化导出返回字段

## [0.6.5] - 2024-10-23

### Changed
- 更新支持的 Laravel 版本

## [0.6.4] - 2024-05-31

### Added
- 增加游标导出方式
- 增加内存配置

### Changed
- 优化联表排序逻辑
- 去掉没用的配置

## [0.6.3] - 2023-12-13

### Performance
- 减少 toBase 调用，优化性能

## [0.6.2] - 2023-12-08

### Changed
- 优化代码 mutation 空字段的返回
- 增加注释

## [0.6.1] - 2023-10-10

### Changed
- 修改校验后返回通过的数据

## [0.6.0] - 2023-06-07

### Added (⚠️ 破坏性变更)

#### 1. Mutation 参数结构调整
Mutation 的参数从直接传递改为包装在 `input` 对象中。

**v0.5.x 语法**:
```graphql
mutation {
  createUser(name: "张三", email: "zhangsan@example.com", age: 25) {
    id
    name
  }
}
```

**v0.6.0+ 语法**:
```graphql
mutation {
  createUser(input: {
    name: "张三",
    email: "zhangsan@example.com",
    age: 25
  }) {
    id
    name
  }
}
```

**影响**: 所有 mutation 调用都需要将参数包装在 `input` 对象中。

#### 2. orderBy 联表排序语法变更
支持联表排序，orderBy 从点号语法改为嵌套结构。

**v0.5.x 语法**:
```graphql
query {
  users(orderBy: [
    { id: DESC },
    { "users.name": ASC }  # 联表排序使用点号
  ]) {
    id
    name
  }
}
```

**v0.6.0+ 语法**:
```graphql
query {
  users(orderBy: [
    { id: DESC },
    { users: { name: ASC } }  # 联表排序使用嵌套结构
  ]) {
    id
    name
  }
}
```

**影响**: 联表排序语法需要修改，单表排序语法保持不变。

#### 3. filters 和 orderBy 默认值移除
filters 和 orderBy 不再提供默认空数组。

**v0.5.x 行为**: 不传时默认为 `[]`

**v0.6.0+ 行为**: 不传时为 `null`

**影响**: 轻微，通常后端会处理 null 值。

### Added
- 支持 DataLoader 延迟加载
- 支持自定义 mutation 的返回类型
- 列表页查询时剔除详情页专用字段
- object type 支持动态修改可用字段
- 支持获取原始字段，便于动态生成语法
- 支持重写关联逻辑
- 支持延迟查询数据
- 允许获取 Resolver 的校验器

### Changed
- 只支持 model 的延迟加载
- 根据关联方式来判断是否进行提前关联
- 调整延迟加载文件名称
- 不允许设置 mutation
- 移除接口，降低复杂度
- 恢复最大查询深度
- 调整 loader 缓存；关联时自动选中字段
- 延迟加载时只构建当前层级的 selection
- dataloader 冗余在 Context 中，保证不同的请求不会相互影响
- 启用延迟加载时，不再进行预加载
- 解耦 mutation 逻辑
- 提高 filters 与 orderBy 初始化优先级
- 优化 filters 与 sortFields 初始化逻辑
- pagination lazy load filters
- 修改字段回调函数

### Fixed
- field resolver 不存在
- 排序有误
- resolver 与 deferrable 关联深度不一致
- 方法调用有误
- 异常提示语有误

## [0.5.13] - 2023-05-15

### Changed
- 优化代码结构

## [0.5.12] - 2022-08-10

### Added
- 支持重写关联逻辑
- 增加 query 查询加载必选字段

### Fixed
- 直接使用操作符无法进行筛选

## [0.5.11] - 2022-07-15

### Added
- 支持联表排序（见下方破坏性变更）

### Fixed
- 排序条件定义为变量时无法读取排序输入值类型

## [0.5.10] - 2022-07-01

### Changed (⚠️ 破坏性变更)

#### orderBy 语法变更：从对象改为数组对象
支持按照前端传递顺序进行排序，orderBy 从单个对象改为对象数组。

**v0.5.9 及之前语法**:
```graphql
query {
  users(orderBy: {
    id: DESC,
    name: ASC
  }) {
    id
    name
  }
}
```

**v0.5.10+ 语法**:
```graphql
query {
  users(orderBy: [
    { id: DESC },
    { name: ASC }
  ]) {
    id
    name
  }
}
```

**影响**: 
- orderBy 参数从单个对象 `{ field: direction }` 改为对象数组 `[{ field: direction }]`
- 这样可以保证排序字段的顺序，按照前端传递的顺序进行排序
- **必须修改**所有使用 orderBy 的查询

## [0.5.9] - 2022-06-20

### Added
- 支持针对字段关闭排序索引失效

## [0.5.8] - 2022-06-15

### Changed
- 优化字段排序，使用函数避免排序错误

## [0.5.7] - 2022-06-10

### Fixed
- 排序条件定义为变量时无法读取排序输入值类型

## [0.5.6] - 2022-06-01

### Changed
- 优化代码结构

## [0.5.5] - 2022-05-20

### Changed
- 优化性能

## [0.5.x 早期版本] - 2021-07-23 至 2022-05-20

### 主要特性
- 基于 Laravel ORM 的 GraphQL 查询库
- 支持 bigInt/mixed/uint 等特殊类型
- 筛选条件的注入
- 关联延迟加载
- 支持自定义 mutation
- 支持排序和分页
- 支持联表查询

---

## 迁移指南

### 从 v0.5.9 或更早版本升级到 v0.5.10+

#### orderBy 语法变更（必须修改）
将单个对象改为对象数组：

```diff
- query {
-   users(orderBy: { id: DESC, name: ASC }) {
-     id
-     name
-   }
- }
+ query {
+   users(orderBy: [
+     { id: DESC },
+     { name: ASC }
+   ]) {
+     id
+     name
+   }
+ }
```

### 从 v0.5.x 升级到 v0.6.0+

#### 1. 修改所有 Mutation 调用
将所有 mutation 的参数包装在 `input` 对象中：

```diff
- mutation {
-   createUser(name: "张三", email: "test@example.com") {
-     id
-   }
- }
+ mutation {
+   createUser(input: {
+     name: "张三",
+     email: "test@example.com"
+   }) {
+     id
+   }
+ }
```

#### 2. 修改联表排序语法（如果使用了）
将点号语法改为嵌套结构：

```diff
- query {
-   users(orderBy: [{ "users.name": ASC }]) {
-     id
-     name
-   }
- }
+ query {
+   users(orderBy: [{ users: { name: ASC } }]) {
+     id
+     name
+   }
+ }
```

#### 3. 检查 Hook 方法重写（如果重写了）
如果 resolver 子类重写了 `beforeDestroy` 或 `afterDestroy`，需要更新方法签名：

```diff
- protected function beforeDestroy(Context $context, Model $model)
+ protected function beforeDestroy(Context $context, Model $model, array $input)
```

### 从 v0.6.7 升级到 v0.6.8+

#### rawSql 操作符已移除
`rawSql` 操作符已被完全移除，需要改为在 resolver 中显式构造查询：

```php
// v0.6.7 及之前（已移除）
{ users(filter: { rawSql: "status = 1" }) { id } }

// v0.6.8+（新方式）
// 在 resolver 中使用 beforeQuery 或其他钩子方法
class UserResolver extends Resolver
{
    protected function beforeQuery(Context $context)
    {
        $query = $this->getModelQuery();
        // 根据业务需求添加复杂的 where 条件
        $query->where('status', '=', 1);
        // 或使用 whereRaw（注意安全性）
        // $query->whereRaw('status = ?', [1]);
    }
}
```

---

## 版本统计

- **当前版本**: v0.6.8
- **发布日期**: 2025-06-16
- **PHP 版本要求**: 8.2+
- **Laravel 版本支持**: 9.x - 13.x
