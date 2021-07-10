<?php
//配置文件

/* 分类菜单图标 */
$_LANG['icon']['watch']          = '手表';
$_LANG['icon']['digital']        = '数码';
$_LANG['icon']['books']          = '书';
$_LANG['icon']['outdoors']       = '户外';
$_LANG['icon']['care']           = '保健';
$_LANG['icon']['drug']           = '药';
$_LANG['icon']['computer']       = '电脑';
$_LANG['icon']['ele']            = '家用电器';
$_LANG['icon']['bed']            = '床';
$_LANG['icon']['makeup']         = '化妆';
$_LANG['icon']['clothes']        = '服装搭配';
$_LANG['icon']['car']            = '汽车';
$_LANG['icon']['package']        = '礼品';
$_LANG['icon']['baby']           = '母婴';
$_LANG['icon']['shoes']          = '鞋';
$_LANG['icon']['heal']           = '医药保健';
$_LANG['icon']['food']           = '食品';
$_LANG['icon']['tangdou']        = '糖豆';
$_LANG['icon']['liangyoufushi1'] = '粮油副食';
$_LANG['icon']['weizhi']         = '位置';
$_LANG['icon']['jucha2']         = '聚茶';
$_LANG['icon']['jinkou']         = '进口';
$_LANG['icon']['shuiguo']        = '水果';
$_LANG['icon']['jiubei']         = '酒杯';
return [
    'set_statistical_chart' => json_decode('{
    "series": [
        {
            "name": "订单个数",
            "type": "line",
            "itemStyle": {
                "normal": {
                    "color": "#6cbd40",
                    "lineStyle": {
                        "color": "#6cbd40"
                    }
                }
            },
            "data": [
                0,
                0,
                0,
                0,
                0,
                0,
                0
            ],
            "markPoint": {
                "itemStyle": {
                    "normal": {
                        "color": "#6cbd40"
                    }
                },
                "data": [
                    {
                        "type": "max",
                        "name": "最大值"
                    },
                    {
                        "type": "min",
                        "name": "最小值"
                    }
                ]
            }
        },
        {
            "type": "force",
            "name": "",
            "draggable": false,
            "nodes": {
                "draggable": false
            }
        }
    ],
    "tooltip": {
        "trigger": "axis",
        "axisPointer": {
            "lineStyle": {
                "color": "#6cbd40"
            }
        }
    },
    "legend": {
        "data": []
    },
    "toolbox": {
        "show": true,
        "orient": "vertical",
        "x": "right",
        "y": "60",
        "feature": {
            "magicType": {
                "show": true,
                "type": [
                    "line",
                    "bar"
                ]
            },
            "saveAsImage": {
                "show": true
            }
        }
    },
    "calculable": true,
    "xAxis": {
        "type": "category",
        "boundaryGap": false,
        "axisLine": {
            "lineStyle": {
                "color": "#ccc",
                "width": 0
            }
        },
        "data": [
            "04-08",
            "04-09",
            "04-10",
            "04-11",
            "04-12",
            "04-13",
            "04-14"
        ]
    },
    "yAxis": {
        "type": "value",
        "axisLine": {
            "lineStyle": {
                "color": "#ccc",
                "width": 0
            }
        },
        "axisLabel": {
            "formatter": ""
        },
        "formatter": "0个"
    },
    "xy_file": []
}', 320),
    #聚合数据查询接口-  cnjiuhe  jiuhe@123
    'JuHeConfig'            => [
        'AppKey' => '800abbb5a7362e3bc42d5fb870a6a3ae',
        'cURL'   => 'http://v.juhe.cn/exp/index',
        'dURL'   => 'http://v.juhe.cn/exp/com',
    ],
    'templateList'          => [
        #'默认'=>'default',
        ['template_file' => 'cloth', 'Name' => '服装'],
    ],
    'iconList'              => $_LANG['icon'],
];

