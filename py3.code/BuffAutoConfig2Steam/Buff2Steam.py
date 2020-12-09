#-*-coding:utf8-*-

import steam.webauth as wa
import requests
from requests import Session
import re
from decimal import Decimal
import time
from random import randint as randt
import json

header = {'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.66 Safari/537.36 Aoyou/VnN1RG0qQ1hkKz0iT1h0WW39_ebQ7Og6EiMFYYb9HbCN9c3PheedkDIN'}
COMMUNITY_URL = 'https://steamcommunity.com'
CREATE_BUY_ORDER = '/market/createbuyorder/'
GAME_DIC = {'STEAM': '753', 'CS': '730', 'DOTA': '570'}
CURRENCY = {
    'CNY': 23,
    'USD': 1,
    'EUR': 3,
    'JPY': 8,
    'AUD': 21,
    'CAD': 20,
    'RUB': 5,
}
SEARCH_URL = f'https://steamcommunity.com/market/search?q='


buff_trade_url = 'https://buff.163.com/api/market/steam_trade?_=1606985806092'

def buff163_login_auth():
    cookie = {'Cookie': 'Device-Id=ksrKqrhnXI3IbCO86D7w; _ga=GA1.2.470761462.1607086108; mail_psc_fingerprint=45b1e99d012b773f63c65a533c20153a; _ntes_nnid=04439bfec1d965edc0aeb61194697258,1607086284303; _ntes_nuid=04439bfec1d965edc0aeb61194697258; Locale-Supported=zh-Hans; game=csgo; _gid=GA1.2.2082947366.1607442976; NTES_YD_SESS=bzDcqGpfyUd6cGFIMHqpDj.SAxYnzYdDQgp_h8PxII7miAPuiaN2.Hs6DijXG7OTHRmya5OhuWW6SCNgMTzYuaTT71pp_FVds7REJnJRQup3Cgh4zHq7IOHt1Zt3XZmiOeyqKH7Lps9sH5h838wS05drZ0YZOeHmcqQ4n9G.L..KUoEJ1LZo3uPByHwwopQeFKoLrK_4UGhpatwqwwAKWBCh9xRjLMxq_Q_S1yAffO.5d; S_INFO=1607528515|0|3&80##|13613905817; P_INFO=13613905817|1607528515|1|netease_buff|00&99|hen&1607047107&netease_buff#hen&410100#10#0#0|&0|null|13613905817; remember_me=U1094423637|oyRcOTztGVqpoxRAmKkkEzYlSsAdK6xr; session=1-DxLamp4SK5-YocjT3dCSxvBn3tcKQziNECWHALSEmknY2046102285; _gat_gtag_UA_109989484_1=1; csrf_token=IjRkZjlkMzk0NGRkNjdjOTc1YzNiNTVhN2QzOTBjNzg1NDJiMTZlM2Ii.ErKB6w.bhnA_CvC_tHsDHq1ZYkSRrs543M'}
    buff_session = Session()
    anthor = 'https://buff.163.com/api/market/steam_trade/batch/info?bot_trades=201209T1268028992&_=1607528632768'
    trade_url = 'https://buff.163.com/market/tradeoffer/4330534345'
    resp = buff_session.get(url='https://buff.163.com/api/market/steam_trade?_=1606985806092', headers=header, cookies=cookie).content.decode('utf-8')
    print(resp)
    json_resp = json.loads(resp)['data']
    print(len(json_resp))
    for each_trade in json_resp:
        steam_trade_url = each_trade['url']
        verify_code = each_trade['verify-code'].split(' ')[1]
        buyer_name = each_trade['bot_name']
        buyer_datetime = each_trade['bot_extra_info']
        game_id = each_trade['appid']
        game_name = each_trade['game']
        good_info = each_trade['items_to_trade']
    return trade_url


def monitor_buff():
    buff_session = Session()
    while True:
        resp = buff_session.get(url='https://buff.163.com/api/market/steam_trade?_=1606985806092', headers=header, cookies=cookie).content.decode('utf-8')
        if len(json.loads(resp)['data']) > 0:
            break


def steam_login_auth(username, passwd):
    requests.
    user = wa.WebAuth(username, passwd)
    session = user.login(twofactor_code=input('请输入 2FA Code:'), language='schinese')
    return session

def deal_exchange():
    pass

def alarm():
    pass

if __name__ == '__main__':
    buff163_login_auth()
