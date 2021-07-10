// if(Java.available){
//     Java.perform(function(){
//         var homeProfileFragmentClazz = Java.use("com.zkunity.yundun.YunDun");
//         // var looperClazz = Java.use("android.os.Looper");
//         // looperClazz.prepare();
//         var mainActivity = homeProfileFragmentClazz.$new();
//         var result = mainActivity.getClientIP();
//         console.log(result);
//         mainActivity.$dispose();
//         send(result)
//     });
// }

// function main() {
//     console.log("Enter the Script!");
//     Java.perform(function x() {
//         console.log("Inside Java perform");
//         var MainActivity = Java.use("com.zkunity.core.SIntentService");
//         // overload 选择被重载的对象
//         MainActivity.onReceiveMessageData.overload('android.content.Context', 'com.igexin.sdk.message.GTTransmitMessage').implementation = function (str) {
//             // 打印参数
//             console.log("original call : str:" + str);
//             // 修改结果
//             var ret_value = "sakura";
//             return ret_value;
//         };
//         // 寻找类型为classname的实例
//         Java.choose("com.zkunity.core.SIntentService", {
//             onReceiveMessageData: function (x) {
//                 console.log("find instance :" + x);
//                 console.log("result of secret func:" + x.secret());
//             },
//             // onComplete: function () {
//             //     console.log("end");
//             // }
//         });
//     });
// }
// setImmediate(main);

// function callFun() {
//     Java.perform(function fn() {
//         console.log("begin");
//         Java.choose("com.zkunity.proxy.ZKUnityPlayerNativeActivity", {
//             onMatch: function (x) {
//                 console.log("find instance :" + x);
//                 console.log("result of fun(string) func:" + x.fun(Java.use("java.lang.String").$new("sakura")));
//             },
//             onComplete: function () {
//                 console.log("end");
//             }
//         })
//     })
// }
// rpc.exports = {
//     callfun: callFun
// };

// function hookOverloads(className, func) {
//   var clazz = Java.use(className);
//   var overloads = clazz[func].overloads;
//   for (var i in overloads) {
//     if (overloads[i].hasOwnProperty('argumentTypes')) {
//       var parameters = [];
//
//       var curArgumentTypes = overloads[i].argumentTypes, args = [], argLog = '[';
//       for (var j in curArgumentTypes) {
//         var cName = curArgumentTypes[j].className;
//         parameters.push(cName);
//         argLog += "'(" + cName + ") ' + v" + j + ",";
//         args.push('v' + j);
//       }
//       argLog += ']';
//
//       var script = "var ret = this." + func + '(' + args.join(',') + ") || '';\n"
//         + "console.log(JSON.stringify(" + argLog + "));\n"
//         + "return ret;"
//
//       args.push(script);
//       clazz[func].overload.apply(this, parameters).implementation = Function.apply(null, args);
//     }
//   }
// }
//
// Java.perform(function() {
//   hookOverloads('com.zkunity.core.SIntentService', 'onReceiveMessageData');
// })

// Interceptor.attach(Module.findExportByName('libsqlite.so', 'sqlite3_prepare16_v2'), {
//       onEnter: function(args) {
//           console.log('DB: ' + Memory.readUtf16String(args[0]) + '\tSQL: ' + Memory.readUtf16String(args[1]));
//       }
// });


// Java.perform(function() {
// 	Java.enumerateLoadedClasses({
// 		onMatch: function(className) {
// 			console.log(className);
// 		},
// 		onComplete: function() {}
// 	});
// });

// Java.perform(function() {
// 	//enter class name here: example android.security.keystore.KeyGenParameterSpec$Builder
// 	//class inside a class is defined using CLASS_NAME$SUB_CLASS_NAME
// 	var class_name = "com.zkunity.config.AppConfig";
// 	var db1 = Java.use(class_name);
// 	var methodArr = db1.class.getMethods();
// 	console.log("[*] Class Name: " + class_name)
// 	console.log("[*] Method Names:")
// 	for(var m in methodArr)
// 	{
// 		console.log(methodArr[m]);
// 	}
// });

// "use strict";
//
// // require("./lib/common");
//
// console.log(`[*] Frida ${Frida.version} on ${Process.arch}`);

// Java.perform(function() {
//   //enter class name here: example android.security.keystore.KeyGenParameterSpec$Builder
// 	//class inside a class is defined using CLASS_NAME$SUB_CLASS_NAME
// 	var class_name = Java.use("com.zkunity.yundun.YunDun");
// 	console.log("11111");
//   //replace FUNC_NAME_HERE with method name you want to hook and remove arg1 or add more if the function has arguments
// 	class_name.getClientIP.implementation = function () {
// 	    this.getClientIP();
// 		console.log("[*] CLASS_NAME:FUNC_NAME was called, args:");
// 		return this.getClientIP();
// 	}
// });

// Frida Java hooking helper class.
//
// Edit the example below the HookManager class to suit your
// needs and then run with:
//  frida -U "App Name" --runtime=v8 -l objchookmanager.js
//
// Generated using objection:
//  https://github.com/sensepost/objection

class JavaHookManager {

  // create a new Hook for clazzName, specifying if we
  // want verbose logging of this class' internals.
  constructor(clazzName, verbose = false) {
    this.printVerbose(`Booting JavaHookManager for ${clazzName}...`);

    this.target = Java.use(clazzName);
    // store hooked methods as { method: x, replacements: [y1, y2] }
    this.hooking = [];
    this.available_methods = [];
    this.verbose = verbose;
    this.populateAvailableMethods(clazzName);
  }

  printVerbose(message) {
    if (!this.verbose) { return; }
    this.print(`[v] ${message}`);
  }

  print(message) {
    console.log(message);
  }

  // basically from:
  //  https://github.com/sensepost/objection/blob/fa6a8b8f9b68d6be41b51acb512e6d08754a2f1e/agent/src/android/hooking.ts#L43
  populateAvailableMethods(clazz) {
    this.printVerbose(`Populating available methods...`);
    this.available_methods = this.target.class.getDeclaredMethods().map((method) => {
      var m = method.toGenericString();

      // Remove generics from the method
      while (m.includes("<")) { m = m.replace(/<.*?>/g, ""); }

      // remove any "Throws" the method may have
      if (m.indexOf(" throws ") !== -1) { m = m.substring(0, m.indexOf(" throws ")); }

      // remove scope and return type declarations (aka: first two words)
      // remove the class name
      // remove the signature and return
      m = m.slice(m.lastIndexOf(" "));
      m = m.replace(` ${clazz}.`, "");

      return m.split("(")[0];

    }).filter((value, index, self) => {
      return self.indexOf(value) === index;
    });

    this.printVerbose(`Have ${this.available_methods.length} methods...`);
  }

  validMethod(method) {
    if (!this.available_methods.includes(method)) {
      return false;
    }
    return true;
  }

  isHookingMethod(method) {
    if (this.hooking.map(element => {
      if (element.method == method) { return true; }
      return false;
    }).includes(true)) {
      return true;
    } else {
      return false;
    };
  }

  hook(m, f = null) {
    if (!this.validMethod(m)) {
      this.print(`Method ${m} is not valid for this class.`);
      return;
    }
    if (this.isHookingMethod(m)) {
      this.print(`Already hooking ${m}. Bailing`);
      return;
    }

    this.printVerbose(`Hookig ${m} and all overloads...`);

    var r = [];
    this.target[m].overloads.forEach(overload => {
      if (f == null) {
        overload.replacement = function () {
          return overload.apply(this, arguments);
        }
      } else {
        overload.implementation = function () {
          var ret = overload.apply(this, arguments);
          return f(arguments, ret);
        }
      }

      r.push(overload);
    });

    this.hooking.push({ method: m, replacements: r });
  }

  unhook(method) {
    if (!this.validMethod(method)) {
      this.print(`Method ${method} is not valid for this class.`);
      return;
    }
    if (!this.isHookingMethod(method)) {
      this.print(`Not hooking ${method}. Bailing`);
      return;
    }

    const hooking = this.hooking.filter(element => {
      if (element.method == method) {
        this.printVerbose(`Reverting replacement hook from ${method}`);
        element.replacements.forEach(r => {
          r.implementation = null;
        });
        return; // effectively removing it
      }
      return element;
    });

    this.hooking = hooking;
  }
}

// SAMPLE Usage:

var replace = function(args, ret) {
  // be sure to check the args, you may have an overloaded method
  console.log('Hello from our new function body!');
  console.log(JSON.stringify(args));
  console.log(ret);
//
  return ret;
}

Java.perform(function () {
  const hook = new JavaHookManager('okhttp3.Request');
  hook.hook('header', replace);
  // hook.unhook('header');
});