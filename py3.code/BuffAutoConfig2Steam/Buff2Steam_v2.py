# from tkinter import *
import tkinter as tk
import threading
import steam.webauth as wa
from requests import Session
import re
import time
from Naked.toolshed.shell import muterun_js
import json
import subprocess
import random
import string
from tkinter import scrolledtext, messagebox

header = {'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.66 Safari/537.36 Aoyou/VnN1RG0qQ1hkKz0iT1h0WW39_ebQ7Og6EiMFYYb9HbCN9c3PheedkDIN'}
letters = string.ascii_lowercase
random_str = ''.join(random.choice(letters) for i in range(20))


class Buff2SteamGui(tk.Frame):
	def __init__(self, master=None):
		super().__init__(master)
		self.master = master
		self.master.title('登录界面')  # 设置窗口的标题
		self.master.geometry('500x400')  # 设置窗口的大小
		self.pack()
		self.auth_quit()
		self.Receive = tk.LabelFrame(self.master, text="显示区", padx=10, pady=10)  # 水平，垂直方向上的边距均为 10
		self.Receive.place(x=10, y=10)
		self.Receive_Window = scrolledtext.ScrolledText(self.Receive, width=59, height=10, padx=10, pady=10, wrap=tk.WORD)
		self.Receive_Window.grid()
		self.usr_name_var = tk.StringVar()
		self.password_var = tk.StringVar()
		self.auth2fa = tk.StringVar()

	def buff163_login_auth(self):
		"""
		Login buff163.com by using puppeteer.
		"""
		self.Receive_Window.insert('end', '准备登录buff163......' + '\n')
		muterun_js('buff_login.js', arguments=random_str)
		time.sleep(1)
		self.Receive_Window.insert('end', 'Buff163登录完成......' + '\n')
		self.Receive_Window.see('end')

	def monitor_buff(self, timer: int = 3600):
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
		buff_session = Session()
		steam_trade_list = []
		monitor_trade_id_list = []
		while True:
			resp = buff_session.get(url='https://buff.163.com/api/market/steam_trade', headers=header,
			                        cookies=cookies).content.decode('utf-8')
			if len(json.loads(resp)['data']) > 0:
				data = json.loads(resp)['data']
				break
			time.sleep(timer)  # 每1小时检测一次
		# print(f'buff163 data -> {data}')
		self.Receive_Window.insert('end', f'buff163 data -> {data}' + '\n')
		for each_data in data:
			steam_trade_list.append(
				{
					'steam_trade_id': each_data['tradeofferid'],
					'join_steam_date': each_data['bot_extra_info'].split('：')[1].strip(),
					'game_id': each_data['appid'],
					'verify_code': each_data['verify_code'].split(' ')[1],
					'trade_item_info': each_data['items_to_trade']
				}
			),
			monitor_trade_id_list.append(each_data['tradeofferid'])
			self.Receive_Window.see('end')
		return steam_trade_list, monitor_trade_id_list

	def steam_login_auth(self, username, passwd, auth2fa):
		"""
		Login steam for config deal request from buff163.
		"""
		user = wa.WebAuth(username, passwd)
		steam_session = user.login(twofactor_code=auth2fa, language='schinese')
		return steam_session

	def deal_exchange(self, steam_session, steam_trade_list, all_trade_ids):
		"""
		Config the trade order from buff163.
		"""
		for each_deal in steam_trade_list:
			trade_id = each_deal['steam_trade_id']
			if trade_id in all_trade_ids:
				self.Receive_Window.insert('end', 'id 已存在, 跳过...' + '\n')
				continue
			steam_order_url = f'https://steamcommunity.com/tradeoffer/{trade_id}'
			steam_trade_url = f'https://steamcommunity.com/tradeoffer/{trade_id}/accept'

			resp = steam_session.get(steam_order_url).text
			partner_id = self.text_between(resp, "var g_ulTradePartnerSteamID = '", "';")
			session_id = steam_session.cookies.get_dict()['sessionid']
			buff_ex_date = each_deal['join_steam_date']

			buyer_join_date = re.findall('trade_partner_member_since trade_partner_info_text ">(.*?)</div>', resp)[0]
			buyer_join_date_list = re.findall('(.*?)年(.*?)月(.*?)日', buyer_join_date)[0]
			steam_ex_date = f'{buyer_join_date_list[0]}-{int(buyer_join_date_list[1]):02d}-{int(buyer_join_date_list[2]) + 1:02d}'
			self.Receive_Window.insert('end', f'steam_ex_date -> {steam_ex_date}' + '\n')
			self.Receive_Window.insert('end', f'buff_ex_date -> {buff_ex_date}' + '\n')
			if buff_ex_date == steam_ex_date:
				self.Receive_Window.insert('end', '新订单是来自于buff163的订单，订单有效......' + '\n')
				post_data = {
					'sessionid': session_id,
					'serverid': '1',
					'tradeofferid': trade_id,
					'partner': partner_id,
					'captcha': ''
				}
				headers = {'Referer': steam_order_url}
				response = steam_session.post(url=steam_trade_url, data=post_data, headers=headers).json()
				if response.get('needs_mobile_confirmation', False):
					return 1
				return 0
			else:
				return -1
		self.Receive_Window.see('end')

	def text_between(self, text: str, begin: str, end: str) -> str:
		start = text.index(begin) + len(begin)
		end = text.index(end, start)
		return text[start:end]

	def tips(self):
		result = messagebox.askquestion(title='鉴权成功', message='鉴权成功，已被授权，请进行下一步登录...')
		if result != 'yes':
			self.master.destroy()
		else:
			messagebox.showinfo(title='Buff163登录', message='准备跳转登录Buff163')
			self.buff163_login_auth()
			self.login_promt()

	def auth_quit(self):
		login = tk.Button(self.master, text='鉴权授权', command=self.tips).place(x=150, y=350)
		quit = tk.Button(self.master, text='退出', command=self.master.destroy).place(x=290, y=350)

	def login_promt(self):
		# 输入框的提示语
		tk.Label(self.master, text="用户名:").place(x=75, y=250, anchor='nw')
		tk.Label(self.master, text="密码  :").place(x=75, y=280, anchor='nw')
		tk.Label(self.master, text="二次验证码  :").place(x=75, y=310, anchor='nw')

		# 两个输入框
		usr_name_var = self.usr_name_var
		password_var = self.password_var
		auth = self.auth2fa

		def res():
			if not usr_name_var.get() or not password_var.get() or not auth.get():
				messagebox.showerror(title='登录错误', message='验证失败，请输入有效值...')
				self.Receive_Window.insert('end', '验证失败，请输入有效值...' + '\n')
			else:
				try:
					steam_session = self.steam_login_auth(usr_name_var.get(), password_var.get(), auth.get())
					if steam_session:
						self.main_monitor(steam_session=steam_session)  # 通过多线程解决调用函数处理时间过长导致窗体卡死问题
				except Exception as e:
					messagebox.showerror(title='登录错误', message='验证失败，请重新输入正确的账号密码和二次验证码...')
					self.Receive_Window.insert('end', '验证失败，请重新输入正确的账号密码和二次验证码...' + '\n')

		tk.Entry(self.master, textvariable=usr_name_var).place(x=150, y=250, anchor='nw')
		tk.Entry(self.master, textvariable=password_var, show='*').place(x=150, y=280, anchor='nw')
		tk.Entry(self.master, textvariable=auth).place(x=150, y=310, anchor='nw')

		tk.Button(self.master, text='登录Steam', command=lambda: thread_it(res)).place(x=150, y=350)
		self.Receive_Window.see('end')

	def main_monitor(self, steam_session):
		self.Receive_Window.insert('end', '成功登录Steam，准备开始监测...' + '\n')
		expire_time = time.strftime('%Y-%m-%d', time.localtime(time.time() + 691200))
		all_trade_ids = []
		while True:
			steam_trade_list, monitor_trade_id_list = self.monitor_buff(timer=3600)
			self.Receive_Window.insert('end', '监测到新订单, 30s后开始确认交易...' + '\n')
			time.sleep(10)
			self.Receive_Window.insert('end', '监测到新订单, 20s后开始确认交易...' + '\n')
			time.sleep(10)
			self.Receive_Window.insert('end', '监测到新订单, 10s后开始确认交易...' + '\n')
			time.sleep(10)
			self.Receive_Window.insert('end', '开始确认交易...' + '\n')
			result = self.deal_exchange(steam_session, steam_trade_list, all_trade_ids)
			if result == -1:
				self.Receive_Window.insert('end', '不是来自buff的有效订单，订单作废，继续监测...' + '\n')
				for each in monitor_trade_id_list:
					all_trade_ids.remove(each)
				continue
			elif result == 0:
				self.Receive_Window.insert('end', '确认buff订单出错，请查看订单...' + '\n')
				messagebox.showerror(title='登录错误', message='确认buff订单出错，请查看订单...')
				break
			else:
				self.Receive_Window.insert('end', '成功确认buff订单...' + '\n')
			for each in monitor_trade_id_list:
				if each in all_trade_ids:
					continue
				else:
					all_trade_ids.append(each)
			today = time.strftime('%Y-%m-%d', time.localtime(time.time()))
			if today == expire_time:
				subprocess.run(f'rm {random_str}_cookie.json', shell=True)
				self.Receive_Window.insert('end', 'Buff163登录有效期已过，准备登出...' + '\n')
				messagebox.showerror(title='登录错误', message='Buff163登录有效期已过，准备登出...')
				break
			self.master.destroy()
		self.Receive_Window.see('end')

def thread_it(func, *args):		# 传入函数名和参数, 通过多线程解决调用函数处理时间过长导致窗体卡死问题
    # 创建线程
    t = threading.Thread(target=func, args=args)
    # 守护线程
    t.setDaemon(True)
    # 启动
    t.start()

if __name__ == '__main__':
	root = tk.Tk()
	app = Buff2SteamGui(master=root)
	app.master.title('Buff2Steam Auto Config')
	app.master.maxsize(1000, 400)
	app.mainloop()

