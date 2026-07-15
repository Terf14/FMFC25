# FMFC Manager

ระบบจัดการทีมฟุตบอลบน PHP + MySQL สำหรับใช้งานผ่าน XAMPP มีฟีเจอร์ผู้เล่น, อคาเดมี, สถิติ, ถ้วยรางวัล, กัปตันทีม และ AI assistant.

## การตั้งค่าหลัก

ค่าเริ่มต้นจะเชื่อมต่อฐานข้อมูล `fc25` ด้วย user `root` และรหัสผ่านว่างตาม XAMPP แต่สามารถ override ด้วย environment variables ได้:

```text
FMFC_DB_HOST=localhost
FMFC_DB_NAME=fc25
FMFC_DB_USER=root
FMFC_DB_PASS=
FMFC_GEMINI_API_KEY=your_google_gemini_api_key
```

นำเข้า `fcfm.sql` เพื่อสร้างฐานข้อมูลและตารางทั้งหมด รวมถึง `team_captain` และ `team_records`.

## หมายเหตุความปลอดภัย

อย่าฝัง API key ลงในไฟล์ PHP โดยตรง ให้ตั้งค่า `FMFC_GEMINI_API_KEY` ผ่าน environment ของ Apache/XAMPP แทน.
