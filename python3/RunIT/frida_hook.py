from loguru import logger
import frida
import sys

class FrindaHook():
	@logger.catch(level='ERROR')
	def __init__(self, hook_package):
		self.dsession = frida.get_remote_device().attach(hook_package)  # 查找模拟器设备并附加到目标进程
		self.dsession.enable_debugger()
		self.hook_code = '/Users/antx/Code/prm/RunIT/jshook.js'
		# self.prt = Console()

	@logger.catch(level='ERROR')
	def message_callback(self, message, data):
		if message['type'] == 'send':
			logger.info(message['payload'])
			# logger.info(message)
		elif message['type'] == 'error':
			logger.info(message['stack'])
		else:
			logger.info(message)

	@logger.catch(level='ERROR')
	def hook(self):
		with open(self.hook_code, 'r+') as f:
			hook_codes = f.read()
			# logger.info(f'jshookcode: {hook_codes}')
		hook_script = self.dsession.create_script(hook_codes)   # 在目标进程里创建脚本
		hook_script.on('message', self.message_callback)  # 注册消息回调
		hook_script.load()  # 加载创建好的javascript脚本
		sys.stdin.read()    # 读取系统输入

	@logger.catch(level='ERROR')
	def dia(self):
		pass

	@logger.catch(level='ERROR')
	def test(self):
		apps = self.dsession.get_frontmost_application()
		logger.info(apps)


if __name__ == '__main__':
	hk_pack = 'com.speedfish.fishpoker'
	frida_hook = FrindaHook(hook_package=hk_pack)
	frida_hook.hook()
	# frida_hook.test()