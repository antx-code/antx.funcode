#-*-coding:utf8-*-

import steam.webauth as wa
from requests import Session
import re
from decimal import Decimal
import time
from random import randint as randt
from Naked.toolshed.shell import muterun_js
import json
from redis import Redis

header = {'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.66 Safari/537.36 Aoyou/VnN1RG0qQ1hkKz0iT1h0WW39_ebQ7Og6EiMFYYb9HbCN9c3PheedkDIN'}

def buff163_login_auth():
    print('准备登录buff163......')
    muterun_js('buff_login.js')
    print('登录完成......')
    # cookie = {'Cookie': 'Id=ksrKqrhnXI3IbCO86D7w; _ga=GA1.2.470761462.1607086108; mail_psc_fingerprint=45b1e99d012b773f63c65a533c20153a; _ntes_nnid=04439bfec1d965edc0aeb61194697258,1607086284303; _ntes_nuid=04439bfec1d965edc0aeb61194697258; Locale-Supported=zh-Hans; game=csgo; _gid=GA1.2.1096537323.1607878503; _gat_gtag_UA_109989484_1=1; NTES_YD_SESS=XrouU0N_.PqdNRe1rZfR0HBLLr_7FBeNj7vaUPyLU4jVAZ_WA7Kw6gH8JA0uTL1PgcVb7E1zWhh8nIKNmPStW7PPL5vvqjQ3HLcoYxYckWviINz9SgCLB1dWtrSxdcbQtQVXXRyypnLKgXa17C9T3iadf6lbk_jaSKjwPhfr9hOo0VNIsFwQpA7Szy_M4yimVFlzD2McqOZMaNdSUrWgE9fSEWDsBTr1v6s6z768CkOD3; S_INFO=1607878526|0|3&80##|13613905817; P_INFO=13613905817|1607878526|1|netease_buff|00&99|hen&1607528515&netease_buff#hen&410100#10#0#0|&0|null|13613905817; remember_me=U1094423637|jnrmtSRxKq6V60MTkoPAaNyLpkn83Zwe; session=1-p9xAC_fzWxoTw2bcjtwO2oyEtiFC1DPf7jPP898LuhLM2046102285; csrf_token=IjE3N2JkN2I0MzBjZjNlMmZhYzcxNDdkZTk1Nzk3ZjI5Y2EzZTc3YWMi.ErfZCQ.A-PY5D0ZMgCtI0QDCu6rY6nHydo'}


def monitor_buff():
    with open('cookie.json', 'r+') as f:
        js_cook = f.read()
    cookie = {'Cookie': js_cook}
    buff_session = Session()
    steam_trade_list = []
    while True:
        resp = buff_session.get(url='https://buff.163.com/api/market/steam_trade?_=1606985806092', headers=header, cookies=cookie).content.decode('utf-8')
        if len(json.loads(resp)['data']) > 0:
            data = json.loads(resp)['data']
            break
        time.sleep(randt(1800, 7200))   # 每30分钟-2小时检测一次
    for each_data in data:
        steam_trade_list.append(
            {
                'steam_trade_id': each_data['tradeofferid'],
                'join_steam_date': each_data['bot_extra_info'],
                'game_id': each_data['appid'],
                'verify_code': each_data['verify_code'].split(' ')[1],
                'trade_item_info': each_data['items_to_trade']
            }
        )
    return steam_trade_list


def steam_login_auth(username, passwd):
    user = wa.WebAuth(username, passwd)
    steam_session = user.login(twofactor_code=input('请输入 2FA Code:'), language='schinese')
    return steam_session


def deal_exchange(steam_session, steam_trade_list):
    trade_id = steam_trade_list['steam_trade_id']
    steam_trade_url = f'https://steamcommunity.com/tradeoffer/{trade_id}'
    resp = steam_session.get(url=steam_trade_url).content.decode('utf-8')
    buyer_join_date = re.findall('(.*?)', resp)[0]
    if buyer_join_date == steam_trade_list['join_steam_date']:
        post_data = {

        }
        steam_session.post(url='', data=post_data)
        print('成功确认交易请求，请在手机端确认......')


if __name__ == '__main__':
    print('请先登录buff和steam账号......')
    print('正在跳转......')
    buff163_login_auth()
    steam_session = steam_login_auth(input('请输入Steam账号：'), input('请输入Steam密码：'))
    while True:
        steam_trade_list = monitor_buff()
        deal_exchange(steam_session, steam_trade_list)

