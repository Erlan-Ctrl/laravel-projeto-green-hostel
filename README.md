# 🌱 Green Hostel

![Laravel](https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)
![Figma](https://img.shields.io/badge/Figma-F24E1E?style=for-the-badge&logo=figma&logoColor=white)

---

**Green Hostel** é uma aplicação desenvolvida em **Laravel** que tem como objetivo facilitar a busca de hostels e hotéis em **Belém** para a **CAP30**, oferecendo uma experiência intuitiva e moderna.  

Além de apresentar opções de hospedagem, o sistema conta com um **chatbot inteligente**, capaz de interagir com os usuários e fornecer informações sobre:  

- 🍽️ **Culinária típica da região**  
- 🏞️ **Pontos turísticos de Belém**  
- 🛒 **Localização de serviços essenciais** (mercados, hospitais, farmácias, etc.)

---

## 🚀 Funcionalidades

- ✅ Busca de hotéis/hostels em Belém  
- ✅ Sistema de login seguro (incluindo autenticação em dois fatores e login social: Google, Facebook, Apple)  
- ✅ Reset de senha com segurança reforçada  
- ✅ Integração com banco de dados relacional/não-relacional  
- ✅ Chatbot com integração de IA para responder dúvidas e dar recomendações  
- ✅ Filtros avançados: preço, localização, sustentabilidade, acessibilidade, avaliações  
- ✅ Integração com gateways de pagamento (Stripe, PayPal, Pix)  
- ✅ Notificações Push (lembretes de reservas, promoções, etc.)  
- ✅ Armazenamento seguro de credenciais  
- ✅ Design oficializado e protótipos no Figma  
- ✅ Documentação de endpoints da API (usuários, reservas, hostels, pagamentos, etc.)  

---

## 🛠️ Tecnologias Utilizadas

- **Laravel** (framework backend principal)  
- **MySQL / PostgreSQL** (modelagem de banco de dados relacional)  
- **JavaScript** (interatividade no frontend)  
- **Figma** (design e prototipagem)  
- **Stripe / PayPal / Pix** (pagamentos)  
- **IA/Chatbot** integrado para interação com usuários  

---

## 📌 Planejamento de Desenvolvimento

O projeto está sendo gerido via **Trello**, dividido em sprints e colunas de progresso:

- **Sprint 2**: foco no desenvolvimento da IA, conclusão de telas e oficialização do design  
- **Planejamento**: definição de testes, integração de pagamentos, banco de dados e segurança  
- **A Fazer**: integração do modelo de IA, autenticação, armazenamento seguro, responsividade  
- **Em Progresso**: implementação de autenticação/autorizações, integração com banco de dados e lógica de filtros  
- **Em Revisão/Testes**: filtros de busca, performance e usabilidade  
- **Concluído**: design oficializado, definição da IA, escolha de framework, arquitetura da API e documentação  

---

## ⚙️ Instalação e Uso

Clone o repositório:

```bash
git clone https://github.com/Erlan-Ctrl/laravel-projeto-green-hostel.git
```
Instale as dependências do Laravel:
```
composer install
````

Copie o arquivo .env.example e configure:
````
cp .env.example .env
````
Gere a chave da aplicação:
````
php artisan key:generate
````
Execute as migrations:
````
php artisan migrate
````
Inicie o servidor:
````
php artisan serve
````

Acesse no navegador:
👉 localhost:8000

📚 Documentação da API

Usuários: cadastro, login, autenticação 2FA, login social

Reservas: criar, listar e gerenciar reservas de hostels

Hostels: listagem, filtros por localização, preço, acessibilidade

Pagamentos: integração com Stripe, PayPal, Pix

Notificações: envio de alertas e promoções

👥 Equipe

Desenvolvimento Backend (Erick Erlan, Cauã Girard, Juliana Silva)

Desenvolvimento Frontend (Erick Erlan, Cauã Girard, Juliana Silva, Thamyres Victoria,)

Design/UI (Leandro Saint)

Integração e Testes (Erick Erlan)

🔒 Segurança

LGPD/GDPR compliance

Criptografia de dados sensíveis

Autenticação em dois fatores

Armazenamento seguro de credenciais

📅 Status Atual

✔️ Estrutura do projeto criada
✔️ Design oficializado
✔️ Arquitetura da API definida
🔄 Implementação da IA em progresso
🔄 Integração com banco de dados em andamento

⚡ Projeto desenvolvido para a CAP30, com foco em inovação, usabilidade e valorização da cultura amazônica.
