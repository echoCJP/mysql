# 一个简单的PHP Mysql数据库操作类

## 依赖
- pdo

## 安装
1. composer 安装 ``` composer require yob/mysql ```
2. 引入/vendor目录下的autoload.php ``` require 'vendor/autoload.php'; ```

## 初始化
```
//配置
$config=[
    'host'=>'127.0.0.1',
    'port'=>3306,
    'dbname'=>'',
    'user' => '',
    'password' => ''
];

//推荐使用函数进行实例化,后续操作更加方便
function M($table='null'){
      static $_db;
      if(!$_db){
          $_db=new \Yob\Mysql($config);
      }
      return $_db->table($table);
  }
```
### 增
```
M('user')->add(['user'=>'yob','pass'=>'password']);
```

### 删
```
M('user')->where(['user'=>'yob'])->delete();
```

### 改
```
M('user')->where(['user'=>'yob'])->update(['pass'=>'password2']);
```

### 查找一条
```
M('user')->where(['user'=>'yob'])->find();
```

### 查找全部
```
M('user')->get();
```

### 条件查找
```
M('user')->where(['user'=>'yob'])->get();
```

### 分页查找
```
M('user')->page(1)->get();
```

### 字段查找
```
M('user')->field('user')->get();
```

### 排序
```
M('user')->order('id desc')->get();
```

### join
```
M('user')->join('user_info on user_info.user_id=user.id')->get();
```

### debug 仅打印sql不执行
```
M('user')->debug()->get();
```

### 执行原生sql
```
M('user')->query("select * from user");
```

### 返回原生对象
```
M()->pdo();
```
