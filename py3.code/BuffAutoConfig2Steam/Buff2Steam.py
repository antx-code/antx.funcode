#-*-coding:utf8-*-

import steam.webauth as wa
from requests import Session
import re
import time
from Naked.toolshed.shell import muterun_js
import json
import subprocess
import random
import string

header = {'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.66 Safari/537.36 Aoyou/VnN1RG0qQ1hkKz0iT1h0WW39_ebQ7Og6EiMFYYb9HbCN9c3PheedkDIN'}
letters = string.ascii_lowercase
random_str = ''.join(random.choice(letters) for i in range(20))


def buff163_login_auth():
    """

    Login buff163.com by using puppeteer.

    """
    print('准备登录buff163......')
    muterun_js('buff_login.js', arguments=random_str)
    time.sleep(5)
    print('登录完成......')


def monitor_buff(timer: int=3600):
    """

    Monitor new order trade requests every hour

    """
    with open(f'{random_str}_cookie.json', 'r+') as f:
        js_cook = json.loads(f.readline())
        cookie = ''
        for each in js_cook:
            cookie = cookie + f'{each["name"]}={each["value"]};'
        cookie = cookie[:-1]
    cookies = {'Cookie': cookie}
    # print(cookies)
    buff_session = Session()
    steam_trade_list = []
    while True:
        resp = buff_session.get(url='https://buff.163.com/api/market/steam_trade?_=1606985806092', headers=header, cookies=cookies).content.decode('utf-8')
        if len(json.loads(resp)['data']) > 0:
            data = json.loads(resp)['data']
            break
        time.sleep(timer)   # 每1小时检测一次
    for each_data in data:
        steam_trade_list.append(
            {
                'steam_trade_id': each_data['tradeofferid'],
                'join_steam_date': each_data['bot_extra_info'].split(': ')[1].strip(),
                'game_id': each_data['appid'],
                'verify_code': each_data['verify_code'].split(' ')[1],
                'trade_item_info': each_data['items_to_trade']
            }
        )
    return steam_trade_list


def steam_login_auth(username, passwd):
    """

    Login steam for config deal request from buff163.

    """
    user = wa.WebAuth(username, passwd)
    steam_session = user.login(twofactor_code=input('请输入 2FA Code:'), language='schinese')
    return steam_session


def deal_exchange(steam_session, steam_trade_list):
    """

    Config the trade order from buff163.

    """
    for each_deal in steam_trade_list:
        trade_id = each_deal['steam_trade_id']
        steam_order_url = f'https://steamcommunity.com/tradeoffer/{trade_id}'
        steam_trade_url = f'https://steamcommunity.com/tradeoffer/{trade_id}/accept'
        resp = steam_session.get(url=steam_order_url).content.decode('utf-8')
        buyer_join_date = re.findall('trade_partner_member_since trade_partner_info_text ">(.*?)</div>', resp)[0]
        partner_id = re.findall('您正在与 <a href="https://steamcommunity.com/profiles/(.*?)" data-miniprofile="',resp)[0]
        session_id = re.findall('var g_sessionID = "(.*?)";', resp)[0]
        buyer_join_date_list = re.findall('(.*?)年(.*?)月(.*?)日', buyer_join_date)[0]
        tars = each_deal['join_steam_date'].split('-')
        if buyer_join_date_list[0] == tars[0] and int(buyer_join_date_list[1]) == int(tars[1]) and int(buyer_join_date_list[2]) == int(tars[2]):
            post_data = {
                'sessionid': session_id,
                'serverid': 1,
                'tradeofferid': trade_id,
                'partner': partner_id,
            }
            steam_session.post(url=steam_trade_url, data=post_data).content.decode('utf-8')
            print('成功确认交易请求，请在手机端确认......')
        else:
            print('新订单不是来自于buff163的订单，订单作废......')


if __name__ == '__main__':
    print('请先登录buff和steam账号......')
    print('正在跳转......')
    buff163_login_auth()
    steam_session = steam_login_auth(input('请输入Steam账号：'), input('请输入Steam密码：'))
    expire_time = time.strftime('%Y-%m-%d', time.localtime(time.time() + 691200))
    while True:
        steam_trade_list = monitor_buff(timer=3600)
        print('监测到新订单, 30s后开始确认交易......')
        time.sleep(30)
        print('开始确认交易......')
        deal_exchange(steam_session, steam_trade_list)
        today = time.strftime('%Y-%m-%d', time.localtime(time.time()))
        if today == expire_time:
            subprocess.run(f'rm {random_str}_cookie.json', shell=True)
            print('cookie is expire, loguout')
            break