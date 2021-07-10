#-------------------------------------------------------------------------------
# Name:        msg_abstract.py
# Purpose:
#
# Author:      shikano.takeki
#
# Created:     12/12/2017
# Copyright:   (c) shikano.takeki 2017
# Licence:     <your licence>
#-------------------------------------------------------------------------------
# -*- coding: utf-8 -*-
from abc import ABCMeta, abstractmethod


class MsgFactoryAbstract(metaclass=ABCMeta):
    """
    """
    @abstractmethod
    def msg_create(self):
        pass

class MsgFactory1(MsgFactoryAbstract):
    """
    """
    def msg_create(self):
        return Msg1()

class MsgAbstract(metaclass=ABCMeta):
    """
    """
    @abstractmethod
    def call_msg(self):
        pass
class Msg1(MsgAbstract):
    """
    """
    def call_msg(self):
        return "Yesなら 1  No なら 2 を入力してください。: "

class Msg2(MsgAbstract):
    """
    """
    def call_msg(self):
        return "入力値は 数値 で入力してください。"

class Msg3(MsgAbstract):
    """
    """
    def call_msg(self):
        return "正しい数値を入力してください。"

class Msg4(MsgAbstract):
    """
    """
    def call_msg(self):
        return "手動で1行ずつ実行しますか？ファイルを読み込んで一括で実行しますか？"

class Msg5(MsgAbstract):
    """
    """
    def call_msg(self):
        return "手動で実行なら 1  ファイルを読み込んで実行するなら 2 を入力してください。: "

class Msg6(MsgAbstract):
    """
    """
    def call_msg(self):
        return "実行したいSQL文を入力: "

class Msg7(MsgAbstract):
    """
    """
    def call_msg(self):
        return "正しいSQL文を入力してください。"

class Msg8(MsgAbstract):
    """
    """
    def call_msg(self):
        return "次のSQL文を実行する場合は 1 を、入力を中止する場合は 2 を入力: "

class Msg9(MsgAbstract):
    """
    """
    def call_msg(self):
        return 'SQL文をファイルから読み込んで一括で実行します。読み込むファイルのパスを指定してください。: '

class Msg10(MsgAbstract):
    """
    """
    def call_msg(self):
        return "文字エンコーディングの指定 デフォルトはUTF-8 特に指定しない場合はそのままEnter"

class Msg11(MsgAbstract):
    """
    """
    def call_msg(self):
        return "使用できる値の例...utf_8, shift_jis, euc_jp, cp932, etc...: "

class Msg12(MsgAbstract):
    """
    """
    def call_msg(self):
        return "実行結果を書き込むファイルのパスを指定 ex.. /var/log/aaa.log"

class Msg13(MsgAbstract):
    """
    """
    def call_msg(self):
        return "コンソールに出力する場合は 1 を入力してください:"

class Msg14(MsgAbstract):
    """
    """
    def call_msg(self):
        return "Unicodeエラーが検出されました。エラー内容に従って対処をしてください。"

class Msg15(MsgAbstract):
    """
    """
    def call_msg(self):
        return "このままCOMMITする場合は 1 , ROLLBACKする場合は 2 を入力してください。:"
class Msg16(MsgAbstract):
    """
    """
    def call_msg(self):
        return __file__ + "is ended."

def main():
    pass

if __name__ == '__main__':
    main()
