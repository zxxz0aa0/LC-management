# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## 專案概述

LC-management is a long-term care service management system based on the Laravel 10 framework. It primarily handles customer, order, driver, and location management, manual scheduling, data analysis, and Excel import/export functionality. The system features production-grade concurrency security and can safely handle simultaneous operations by multiple users.

### 關鍵技術堆疊
- **後端**: Laravel 10.x + PHP 8.1+
- **前端**: Vite + Tailwind CSS + Alpine.js + AdminLTE 3.2
- **資料庫**: MySQL with JSON column support + 併發安全性約束
- **認證**: Laravel Breeze
- **Excel 處理**: maatwebsite/excel 3.1+
- **併發控制**: SELECT FOR UPDATE + 原子化序列號 + UUID 群組ID
- **開發工具**: Laravel Pint (程式碼格式化) + IDE Helper

## 核心開發理念：Vibe Coding

### 開發節奏
- **優先級**: 能跑 > 完美 > 理論
- **推進方式**: 合理假設 + 持續前進，不因需求不明而卡住
- **優化時機**: 先讓功能跑起來，再逐步重構優化
- **決策風格**: 給明確建議，不丟多選題

### 互動原則
- 把我當成「邊做邊想的開發者」，目前為剛開始學習寫程式的階段
- 不要過度教學，除非我主動問「為什麼」
- 發現更好做法時，提供「不中斷 vibe 的替代方案」
- 使用繁體中文回應

## Context Engineering 行為模式

### 必須持續記住
- **當前目標**: 正在做什麼功能/解什麼問題
- **技術棧**: 使用的框架、語言、工具
- **命名風格**: 變數、函式、資料表的命名習慣
- **資料結構**: 已存在的 Model、欄位、關聯
- **既有邏輯**: 已寫好的 Controller、Route、View 結構

### Context 不足時
- **不要**: 長篇提問打斷節奏
- **要做**: 用最小干擾的方式補齊假設
- **範例**: 「假設你的 users 表有 email 欄位，這樣寫...」

## 回應格式規範

### 標準結構
1. **先給 Code** - 可直接複製貼上執行
2. **簡短說明** - 一兩句話講為什麼這樣做
3. **補充（選用）** - 其他做法僅簡短提及
4. **避免重複** - 不要每次都重講已知的 Context
5. **分段輸出** - 大段 code 用「先寫關鍵部分 → 確認方向 → 補完整」

### 程式碼品質要求
- 清楚、可讀、可直接用
- 不過度抽象
- 不為了設計模式而設計
- 符合既有 Context 的命名與結構
- 直接定位問題 → 給 patch 或修改建議

### 當有多種解法時
- **主推一個**: 最順手、最有 vibe 的做法
- **其他補充**: 一句話帶過即可

## 請避免的行為

- 每次都重新解釋基礎觀念
- 給「這要看你需求」的模稜兩可回答
- 在我說「先這樣」時堅持完美方案
- 過度使用設計模式或抽象層
- 打斷開發節奏去問一堆確認問題
- 更改不相關的程式碼或是名稱
- 不要複述我的問題或重新解釋已經做過的功能
- 不要給「完整教學文」，只給差異部分

### Code 輸出原則
- **小改動**: 只給要改的那幾行 + 位置說明
- **新功能**: 給完整 code，但省略重複的 boilerplate
- **重構**: 先問「要看完整檔案還是只看改動？」

## Token 節省實戰技巧

### 1. 使用「增量式開發」
壞習慣：
- 我：「幫我寫一個完整的客戶管理系統」
- AI：（輸出 500 行 code）

好習慣：
- 我：「先做客戶列表的 Controller」
- AI：（只給 Controller，30 行）
- 我：「加上分頁」
- AI：（只給分頁那段，5 行）

### 2. 善用「這個先不管」
當我說「這個先不管」時，請：
- 記住但不展開
- 不要每次都提醒我「還有這個沒做喔」

### 3. Code Review 模式
當我說「檢查這段」時：
- 只指出問題 + 修正方案
- 不要重新寫一遍完整檔案
