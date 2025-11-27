@echo off
echo Configurando tunnel publico estavel...
echo.

echo 1. Acesse: https://ngrok.com/signup
echo 2. Crie conta gratuita
echo 3. Va para: https://ngrok.com/dashboard  
echo 4. Copie seu authtoken
echo.

set /p token="Cole seu authtoken aqui: "
echo.

echo Configurando ngrok...
.\ngrok.exe config add-authtoken %token%

echo.
echo Iniciando tunnel estavel...
.\ngrok.exe http 8000

pause
