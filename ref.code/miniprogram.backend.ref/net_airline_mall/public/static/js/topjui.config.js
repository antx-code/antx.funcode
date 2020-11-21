/**
 * 配置文件说明
 * @type {string}
 * topJUI.language: 消息提示框的中文提示，可根据情况调整
 *
 */
var myConfig = {
    config: {
        pkName: 'uuid', //数据表主键名，用于批量提交数据时获取主键值
        singleQuotesParam: true, //是否对批量提交表格选中记录的参数值使用单引号，默认为false，true:'123','456'，false:123,456
        datagrid: {
            page: 'page', //提交到后台的显示第几页的数据
            size: 'rows', //提交到后台的每页显示多少条记录
            total: 'total', //后台返回的总记录数参数名
            rows: 'rows' //后台返回的数据行对象参数名
        },
        postJson: false, //提交表单数据时以json或x-www-form-urlencoded格式提交，true为json，false为urlencoded
        appendRefererParam: false, //自动追加来源页地址上的参数值到弹出窗口的href或表格的url上，默认为false不追加
        statusCode: {
            success: 200, //执行成功返回状态码
            failure: 300 //执行失败返回状态码
        }
    },
    language: {
        message: {
            title: {
                operationTips: "操作提示",
                confirmTips: "确认提示"
            },
            msg: {
                success: "操作成功",
                failed: "操作失败",
                error: "未知错误",
                checkSelfGrid: "请先勾选中要操作的数据前的复选框",
                selectSelfGrid: "请先选中要操作的数据",
                selectParentGrid: "请先选中主表中要操作的一条数据",
                permissionDenied: "对不起，你没有操作权限",
                confirmDelete: "你确定要删除所选的数据吗？",
                confirmMsg: "确定要执行该操作吗？"
            }
        },
        edatagrid: {
            destroyMsg: {
                norecord: {
                    title: '操作提示',
                    msg: '没有选中要操作的记录！'
                },
                confirm: {
                    title: '操作确认',
                    msg: '确定要删除选中的记录吗？'
                }
            }
        }
    },
    l: '4e0d9a4c949740e8cbafe90759bc9148da8ed41406bbd002e17ab7381502f10f7d41a54a93fc790671be9e02b82b6944644802e27b4c4482'
}
