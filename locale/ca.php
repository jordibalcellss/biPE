<?php

define("username","nom d'usuari");
define("password","contrasenya");
define("login_submit","entra");
define("both_fields_required","no deixeu cap camp buit");
define("both_fields_required_log","camps buits");
define("unauthorized","no teniu pas permís");
define("unauthorized_log","no autoritzat");
define("user_and_or_password_incorrect","usuari i/o contrasenya incorrecta");
define("logged_in","autenticat");

define("greeting","hola");
define("logout","tanca la sessió");

define("log","registra temps");
define("bulk_log","registra temps en massa");
define("timesheet","el meu temps");
define("tasks","projectes");
define("clients","clients");
define("accounting","comptable");
define("phases","previsió");
define("expenses","despeses");
define("invoicing","facturació");
define("overview","visió general");
define("periodic","periòdic");

define("log_task","registra");
define("log_discard","descarta");
define("log_save_next_day","desa-ho i vés al següent dia");
define("log_what_tasks_today","en què has treballat aquesta jornada?");
define("log_for_how_long","quant de temps hi has dedicat?");
define("log_not_needed","tot bé, no cal que registris<br/>més temps encara");

define("task_weekend_nothing","res");
define("task_holiday","vacances");
define("task_off_sick","malaltia/metge");
define("task_leave","baixa");
define("task_off","personal");
define("task_unpaid","no cobrat");

define("hours","h");
define("hours_word","hores");
define("remaining","restants");
define("spent","dedicades");
define("task","projecte");
define("in_what","en què");
define("day","jorn");
define("days","dies");
define("days_abbr","d");
define("duration","temps dedicat");
define("actions","accions");
define("remove","esborra");
define("previous_page","pàgina anterior");
define("next_page","pàgina següent");
define("export_timesheet_csv","descarrega el meu temps per a full de càlcul");
define("export_csv","exporta csv");
define("no_records_yet","encara no hi ha cap registre");
define("empty_search","la cerca no ha tornat cap registre");
define("code","codi");
define("name","nom");
define("edit","edita");
define("status","estat");
define("add","afegeix");
define("manage","gestiona");
define("details","detalls");
define("personal_expenses","despeses personals");
define("declare_personal_expenses","declara ".personal_expenses);
define("total","total");
define("difference","diferència");
define("overtime","extres");

define("client","client");
define("address","adreça");
define("city","població");
define("postcode","codi postal");
define("vat_code","núm. fiscal");
define("email","correu-e");
define("phone","telèfon");

define("save","desa");
define("filter","filtra");
define("clear","reseteja");
define("name_cannot_be_empty","el nom no pot ser pas buit");
define("amount_cannot_be_empty","l'import no pot ser pas buit");
define("value_cannot_be_empty","el valor no pot ser pas buit");
define("date_cannot_be_empty","la data no pot ser pas buida");
define("description_cannot_be_empty","la descripció no pot ser pas buida");
define("edit_success","s'ha editat correctament");
define("add_success","s'ha afegit correctament");
define("remove_success","s'ha eliminat correctament");
define("back","torna");
define("is_free_software","és programari lliure");

define("rate","tarifa");
define("currency","€");
define("rate_math",currency."/h");
define("date","data");
define("from","de");
define("to","a");
define("deadline","data límit");
define("active","actiu");
define("category","categoria");
define("invalid_deadline","la ".deadline." és invàlida, useu format
dd-mm-aaaa");
define("invalid_date","la ".date." és invàlida, useu format dd-mm-aaaa");

define("description","descripció");
define("amount","import");
define("quotation","honoraris/pressupost");
define("invoice","factura");
define("sent","enviada");
define("settled","liquidada");
define("nature","tipus");
define("income","ingrés");
define("expense","despesa");
define("no","no");
define("yes","sí");
define("who","qui");

define("estimated_income","pressupostat a favor");
define("estimated_expense","pressupostat en contra");
define("estimated_revenue","rendiment estimat");
define("invoiced_income","facturat a favor");
define("invoiced_expense","facturat en contra");
define("revenue","rendiment");
define("settled_income","cobrat");
define("settled_expense","pagat");

define("value","valor");
define("mileage","km");
define("mileage_value","recompte de quilometratge");
define("receipt","rebut");
define("receipt_value","valor d'un rebut");
define("paid_back","retornat");

define("bulk_log_advice","tingueu en compte que el temps afegit amb aquest
formulari s'anota amb la data del darrer registre<br/><br/>es pot introduir
fins a 1000 hores cada vegada");

define("bulk_log_excess","admet fins a 999 hores cada volta");
define("are_now_orphans","són ara orfes");

define("mon","dl");
define("tue","dt");
define("wed","dc");
define("thu","dj");
define("fri","dv");
define("week","setmana");
define("month","mes");
define("year","any");
define("this_week","aquesta setmana");
define("this_month","aquest mes");
define("this_year","enguany");
define("weekly_view","vista setmanal +");

define("periodic_advice","aquesta pàgina mostra una taula amb les jornades de
la setmana actual més resum anual i una altra taula amb el temps que cada
treballador ha dedicat a projectes (o fora de l'oficina) durant cada any<br />
<br />els dies feiners es calculen restant els caps de setmana més 12 dies de
festa (l'1 i el 6 de gener, el divendres sant i el dilluns de Pasqua, l'1 de
maig, el 24 de juny, el 15 d'agost, l'11 de setembre, l'1 de novembre i el 8,
25 i 26 de desembre)");

?>
