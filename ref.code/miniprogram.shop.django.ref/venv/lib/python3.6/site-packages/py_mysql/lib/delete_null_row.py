#-------------------------------------------------------------------------------
# Name:        delete_null_row.py
# Purpose:     テキストファイルを読み込み、空白行を削除した文字列リストを返す。
#
# Author:      shikano.takeki
#
# Created:     08/12/2017
# Copyright:   (c) shikano.takeki 2017
# Licence:     <your licence>
#-------------------------------------------------------------------------------
# -*- coding: utf-8 -*-
import os
import codecs

class DeleteNullRow:
    """DeleteNullRow

    """
    def __init__(self):
        """コンストラクタ

        :param encode: 文字エンコーディング.
        """
        # self.encode = encode

    def read_text_file(self, dir_path: str, encode=None):
        """ファイルを読み込んで空白行を削除した文字列群をリストに格納し返す.

        :param dir_path: ディレクトリパス.
        :param encode: 文字エンコーディングの指定 デフォルトはUTF-8.
        """
        if encode is None:
            encode = 'utf_8'
        # 挿入文字列を格納しておくリスト
        ins_str = list()
        try:
            with codecs.open(dir_path, mode='r', encoding=encode) as file:
                for line in file:
                    if line in {'\n', '\r', '\r\n' }:
                        continue
                    else:
                        ins_str.append(line.rstrip())
        except FileNotFoundError as e:
            print("\n存在しないファイルです。パス指定が正しいかどうか確認してください。\n")
            raise e
        except UnicodeError as e:
            print("\n" + e.encoding + ": " + e.reason + "\n")
            print("エラー対象オブジェクト: " + e.object + "\n")
            raise e
        except (LookupError, ValueError) as e:
            print("存在しない、または無効な値です。")
            raise e
        else:
            print("==========================================\n")
            print("Complete loading a file.\n")
            return ins_str

    def write_text_file(self, str_line: str, dir_path: str, mode: str, encode=None):
        """引数で受け取った文字列をファイルに書き込む.

        :param str_line: 書き込む文字列.
        :param dir_path: 出力ファイルのパス.
        :param mode: 書き込みモード
                     'w' = 上書きモード
                     'a' = 追記モード
        :param encode: 文字エンコーディングの指定 デフォルトはUTF-8.
        """
        if encode is None:
            encode = 'utf_8'
        try:
            with codecs.open(r'{}'.format(dir_path), mode=mode, encoding=encode) as file:
                file.write(str(str_line))
        except FileNotFoundError as e:
            print("\n存在しないファイルです。パス指定が正しいかどうか確認してください。\n")
            raise e
        except UnicodeError as e:
            print("\n" + e.encoding + ": " + e.reason + "\n")
            print("エラー対象オブジェクト: " + e.object + "\n")
            raise e
        except (LookupError, ValueError) as e:
            print("存在しない、または無効な値です。")
            raise e
        else:
            pass

    def is_opened(self, dir_path: str, mode: str, encode=None):
        """
        ファイルがオープンできるかどうかの検査用メソッド.

        :param dir_path: ディレクトリパス.
        :param mode: オープンモード.
        :param encode: 文字エンコーディング.
        """
        if encode is None:
            encode = 'utf_8'
        try:
            with codecs.open(r'{}'.format(dir_path), mode=mode, encoding=encode) as file:
                pass
        except FileNotFoundError as e:
            print("\n存在しないファイルです。パス指定が正しいかどうか確認してください。\n")
            return False
        except UnicodeError as e:
            print("\n" + e.encoding + ": " + e.reason + "\n")
            print("エラー対象オブジェクト: " + e.object + "\n")
            return False
        except (LookupError, ValueError, OSError) as e:
            print("存在しない、または無効な値です。")
            return False
        else:
            if mode == 'w' or mode == 'a' and os.path.isfile(dir_path):
                os.remove(dir_path)
            return True


