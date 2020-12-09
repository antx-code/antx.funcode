const puppeteer = require('puppeteer');
var fs = require('fs');

// const random = require('/home/antx/Code/SOC-TI/ti_ds/spider/base')

(async ()=>{
    const browser = await puppeteer.launch({
        // executablePath:'/opt/google/chrome/google-chrome',
		slowMo:100,                                     // 在有输入的情况下的输入文本速度
        headless: false,                                 // 启用无头模式，默认启用
        defaultViewport :{                              // 浏览器窗体的大小设置
            width: 1280,
            height: 800},
        args:[                                          // 一些参数设置，对于过检测隐藏「window.navigator.webdriver」，在puppeteer的launch中将--enbale-automation注释掉即可
            '--disable-extensions',
            '--hide-scrollbars',
            '--disable-bundled-ppapi-flash',
            '--mute-audio',
            '--disable-gpu',
            // '--proxy-server=http://SOCANTXA0GARL0R0:OAMlIL3a@http-proxy-t3.dobel.cn:9180'
        ]
    });
    const page = await browser.newPage();               // 打开浏览器的一个新的page页面
    // await page.emulate(devices['iPhone X']);         // 模拟手机iphone X
    try{
        await page.goto('https://buff.163.com/',);   // Input your target url
        await page.waitFor(3000);
        await page.click('[onclick="loginModule.showLogin()"]');
        await console.log('点击登录');
        // await page.waitForSelector('[id="phoneipt"]', {timeout:0});
        await page.waitFor(3000);
        await console.log('111');
        await page.click('[class="tab0"]');
        await console.log('使用密码验证登录');
        const username = '13613905817';
        const passwd = 'qq136571820';
        await page.type('[class="dlemail j-nameforslide"]', username);
        await page.type('[class="u-pwdtext"]', passwd);
        await console.log('请滑动验证码');
        await page.waitForSelector('[class="j_drop-handler"]', {timeout:0});
        // await page.refresh
    }catch (error){
        console.log('请输入短信验证码，并滑动验证码')
        // await page.click('[onclick="loginModule.showLogin()"]');
        // await page.waitForSelector('[id="mobile-itl-div"]', {timeout:0});
    }finally {
        // await page.click('[onclick="loginModule.showLogin()"]');
        // await page.waitForSelector('[id="mobile-itl-div"]', {timeout:0});
        // await page.waitForSelector('[class="j_drop-handler"]', {timeout:0});
        cooook = await page.cookies();
        await console.log(cooook);
    }

	// await page.type('[id="scform_srchtxt"]', '漏洞');
	// // await page.waitForTimeout(3000);
	// // await page.keyboard.down('Enter');
	// // await page.keyboard.up('Enter');
    // await page.keyboard.press('Enter');
	// // await page.waitForTimeout(15000);
	// await console.log(page.url());

	// fs.readFile('/home/antx/Code/SOC-TI/ti_ds/spider/information/ichunqiu.json', 'utf8', function (err, data) {
    //         if (err) console.log(err);
    //         data = JSON.stringify(page.url());
    //         fs.writeFileSync('/home/antx/Code/SOC-TI/ti_ds/spider/information/ichunqiu.json', data, 'utf8', (err) => {
    //             if (err) throw err;
    //             logger.error(err);
    //             console.log('done');
    //         });
    //     });

    // await page.close();
    // await console.log('页面已关闭')
    // await browser.close()
    // await random.Random(3,7)
    // await console.log('浏览器已关闭')
})();

