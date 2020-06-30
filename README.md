# conecta
Negociação de Sementes

Configurações Módulos Apaches

Habilitar / Descomentar:
  ¢	deflate_module
  ¢	filter_module
  
Configurações Diretivas PHP

Habilitar
  ¢	short_open_tag
  
Ambiente de Desenvolvimento

  Arquivo /config/settings.ini.php	Parâmetro	Valor
    localHosted	1

  Arquivo /config.php	Parâmetro	Valor
    $_EnableSetup  	true
    $_NeedNotSSL     	true
    $_NeedSSL       	false

  Arquivo /template.php	Parâmetro	Valor
    meta property="og:url"	http://dominio
    meta property="og:image"	http://dominio

  Arquivo /app/template.php	Parâmetro	Valor
    meta property="og:url"	http://dominio
    meta property="og:image"	http://dominio

  Tabelas de Estrutura
    c_ref_variedades
