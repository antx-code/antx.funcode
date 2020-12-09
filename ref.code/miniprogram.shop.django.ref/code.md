# 
## Error Code （错误码）

0 请求成功<br>
1 创建 | 更新成功<br>
2 删除成功<br>

999 未知错误<br>

## 
1000 参数错误<br>
1001 资源未找到<br>
1002 未授权(token不合法)<br>
1003 token过期<br>
1004 scope权限不够<br>
1005 授权失败<br>
1006 客户端类型参数错误<br>
1007 未知的HTTP请求异常<br>

###
2001 在数据库中重复<br>

###
4000 请求的bannner不存在<br>

### 
6000 用户不存在<br>
6001 用户地址不存在<br>

## HTTP Status Code（HTTP 状态码）
### 成功
200 查询成功<br>
201 创建 | 更新成功<br>
202 删除成功(代替204)<br>

### 重定向
301 永久性重定向<br>
302 临时性重定向<br>

### 请求错误
400 请求参数错误<br>
401 未授权<br>
403 禁止访问，权限不够<br>
404 没有找到资源or界面<br>

### 服务器错误
500 服务器产生的未知错误<br>