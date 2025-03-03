# Desafio Oliveira Trust

Desenvolvimento de uma API para upload e leitura de arquivos csv/excel.

## 1. Instalação do Projeto

Siga as etapas abaixo para configurar o projeto em sua máquina local.

### 1.1. Clonar o Repositório

Primeiro, clone o repositório para sua máquina local. No terminal, execute o seguinte comando:

```
git clone https://github.com/gfiami/desafio-desenvolvedor.git
```

### 1.2. Navegar até a branch

Navegue para a branch específica onde ocorreu o desenvolvimento do código do desafio

```
git checkout gabriel-fiami-de-souza-pereira
```

### 1.3. Laravel

#### 1.3.1. Dependências do Laravel

Instale as dependências.

```
cd project
composer install
npm install
```

#### 1.3.2. Configurar as Variáveis de Ambiente

Clonar e configurar arquivo .env

As principais definições necessárias no .env são:

-DB_DATABASE|DB_USERNAME|DB_PASSWORD

-PYTHON_API_URL

```
cp .env.example .env
php artisan key:generate
```

#### 1.3.3. Rodar as migrations

O projeto precisa de algumas tabelas para funcionar corretamente. Execute as migrations para criá-las no banco de dados.
```
php artisan migrate
```

### 1.3.4. Linkar storage
Possibilita acessar arquivos para download
```
php artisan storage:link
```

#### 1.3.5. Iniciar servidor

```
php artisan serve

```

### 1.4. Python
Em outro terminal a partir da raiz do projeto iremos configurar a parte em Python do projeto.

#### 1.4.1. Configurar ambiente virtual do Python

Ative o ambiente virtual.

```
cd project-python
python -m venv venv
source venv/Scripts/activate
```

#### 1.4.2. Dependências do Python

Instale as dependências.
```
pip install -r requirements.txt
```

#### 1.4.3. Iniciar servidor

```
cd flask_api
python app.py
```

## 2. Endpoints

### 2.1. Autenticação
O sistema utiliza Laravel Sanctum para autenticação via tokens. As rotas que requerem autenticação devem ser acessadas com um token válido gerado após o login.

#### 2.1.1. Cadastro de usuário

Endpoint para cadastrar um usuário no sistema: 
```
POST api/signup
```

Parâmetros:
```
name: (Obrigatório) Nome do usuário. Deve ser uma string com no máximo 255 caracteres.
email: (Obrigatório) E-mail do usuário. Deve ser um endereço de e-mail válido e único.
password: (Obrigatório) Senha do usuário. Deve ter no mínimo 8 caracteres.
password_confirmation: (Obrigatório) Confirmação da senha. Deve ser igual ao campo password.
```

Exemplo de requisição:
```
{
  "name": "Nome do Usuário",
  "email": "usuario@example.com",
  "password": "senha123",
  "password_confirmation": "senha123"
}

```

#### 2.1.2. Login
Endpoint para logar no sistema e obter um token válido:
```
POST api/login
```

Parâmetros:
```
email: (Obrigatório) E-mail do usuário.
password: (Obrigatório) Senha do usuário.
```
Exemplo de requisição:
```
{
  "email": "usuario@example.com",
  "password": "senha123",
}

```

### 2.2. Arquivos
Após realizar o login, o token de autenticação deve ser incluído nas requisições subsequentes, no cabeçalho ```Authorization``` como um Bearer Token. Além disso, para garantir que a resposta seja recebida no formato JSON, o cabeçalho Accept deve ser configurado como application/json.

O token recebido no login deve ser posto no Authorization Bearer Token.

Além disso, definir no header o Accept como application/json

#### 2.2.1. Upload
Endpoint para enviar um arquivo no formato csv ou excel:
```
POST api/upload
```

Parâmetros no formato multipart/form-data:
```
file: (Obrigatório) Arquivo para ser salvo no servidor.
```

#### 2.2.2. Histórico de uploads
Endpoint para obter lista de arquivos enviados:
```
POST api/history
```

Parâmetros:
```
name:  (Opcional) Nome do arquivo para filtrar a busca.
date(formato yyyy-mm-dd): (Opcional) Data de upload do arquivo para filtrar a busca.
order: (Opcional) Ordenação dos resultados (created_desc, created_asc)
```

Exemplo de resultado:
```
{
    "message": "Histórico de arquivos recuperado do cache.",
    "data": [
        {
            "id": 1,
            "file_name": "InstrumentsConsolidatedFile_20250305_1.csv",
            "file_hash": "614507f7dd406c41e1421ed4f5eaa1d987061856202356c83d09d5dd236e1822",
            "path": "uploads/614507f7dd406c41e1421ed4f5eaa1d987061856202356c83d09d5dd236e1822.csv",
            "created_at": "2025-03-03T22:40:59.000000Z",
            "updated_at": "2025-03-03T22:40:59.000000Z",
            "created_at_formatted": "03/03/2025",
            "download_link": "http://127.0.0.1:8000/storage/uploads/614507f7dd406c41e1421ed4f5eaa1d987061856202356c83d09d5dd236e1822.csv"
        }
    ]
}
```

#### 2.2.3. Conteúdo do arquivo
Endpoint para obter o conteúdo do arquivo (o file_hash pode ser obtido no histórico de uploads):
```
GET api/content/{file_hash}
```

OBS.: Os resultados sempre são páginados com no máximo 20 resultados por página, pois filtros com apenas rpt_dt podem trazer muitos resultados, o que pode afetar a performance. Para melhorar isso seria possível obrigar que o usuário enviasse tanto rpt_dt quanto tckr_symb quando quiser filtrar, evitando erros de tempo de execução.

Parâmetros:
```
page: (Opcional - padrão = 1) Página do conteúdo.
rpt_dt: (Opcional) Filtra resultados com a coluna "RptDt" igual ao valor desejado.
tckr_symb: (Opcional) Filtra resultados com a coluna "TckrSymb" igual ao valor desejado.
```

Exemplo de Endpoint e Filtros:
```
GET api/content/0d6a99fba8069d353c918bfd007e5525c24935d79721b8de5cfe81a6c5a1ac39
{
  rpt_dt: 2024-08-23,
  tckr_symb:A1EG34R
}
```

Exemplo de resultado:
```
{
    "data": [
        {
            "CrpnNm": "AEGON LTD.",
            "ISIN": "BRA1EGBDR002",
            "MktNm": "EQUITY-CASH",
            "RptDt": "2024-08-23",
            "SctyCtgyNm": "BDR",
            "TckrSymb": "A1EG34R"
        }
    ],
    "message": "Conteúdo do arquivo recuperado com sucesso."
}
```