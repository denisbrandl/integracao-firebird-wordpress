ORIENTAÇÕES:

- É necessário habilitar no php.ini o suporte ao firebird e o PDO_Firebird
- Necessário uma instalação do wordpress com o woocommerce habilitado
- Instale o plugin Integracao-TSD que está disponível na pasta plugin
 - Após a instalação e ativação, dentro do painel de administração do wordpress, vá em Configurações > Integração Firebird,
 e configure uma chave de acesso a integração de dados

- Copiar o conteúdo do diretório enviarProdutos para o document root do apache do servidor onde está o firebird.
- Renomear o arquivo env-dist para .env
 - Editar o arquivo .env substituindo os valores de acesso ao banco de dados, configurando a URL para acesso wordpress, chave de acesso a API
 - Adicionar também o codigo de um usuário que será usado para registrar o orçamento

- Copiar o conteúdo do diretório integracaoWordpress para a raiz de onde está o wordpress
- Acessar o endereço {endereco}/enviarProdutos/enviar-produtos.php para fazer a busca por produtos (por enquanto limitado em 5) a serem enviados para o wordpress
 - Onde {endereco} é a url de acesso ao apache do servidor onde está o firebird
 - Será retornado um JSON informando os produtos que foram cadastrados ou atualizados

- Fazer um novo pedido no woocommerce;

- Acessar o endereço {endereco}/integracao/exportacao/exportacao.php
 - Onde {endereco} é a url de acesso ao wordpress
 - Será retornado um JSON com os pedidos do woocommerce