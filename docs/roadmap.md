# Roadmap — FitnessTracker

## Milestone 1 — Fondamenta dominio e layout

Stato: in corso.

Obiettivo: avere progetto Symfony avviabile, layout Tabler, database SQLite, utente automatico, palestra, attrezzature, esercizi e fixture iniziali.

Contenuto:

- utente automatico;
- profilo palestra;
- archivio attrezzature;
- attrezzature disponibili nella palestra;
- archivio esercizi;
- tracking mode esercizi;
- dashboard iniziale;
- documentazione base;
- test unitari minimi.

## Milestone 2 — Archivio palestra, attrezzature ed esercizi

Obiettivo: gestire cosa è disponibile nella palestra e filtrare gli esercizi compatibili.

Da fare:

- pagina lista attrezzature;
- pagina dettaglio attrezzatura;
- pagina attrezzature disponibili;
- toggle disponibile/non disponibile;
- pagina lista esercizi;
- pagina dettaglio esercizio;
- filtro per attrezzatura, muscoli, tracking mode e disponibilità.

## Milestone 3 — Schede di allenamento

Obiettivo: creare schede riutilizzabili, ad esempio lunedì gambe, mercoledì braccia, full body, push/pull/legs.

Da fare:

- entità `WorkoutPlan`;
- entità `WorkoutPlanExercise`;
- ordinamento esercizi;
- serie/reps/tempo previsti;
- progressione per esercizio;
- supporto esercizi cardio e corpo libero.

## Milestone 4 — Diario allenamenti reali

Obiettivo: partire da una scheda e registrare quello che è stato realmente fatto.

Da fare:

- entità `WorkoutSession`;
- entità `ExerciseSession`;
- entità `SetLog`;
- sessioni completate, parziali, saltate;
- peso, reps, RIR, cedimento, percezione carico;
- durata, distanza, intensità per cardio e tempo.

## Milestone 5 — Calibrazione iniziale

Obiettivo: creare una sessione dedicata per stimare i carichi iniziali per esercizio.

Da fare:

- sessione di tipo calibrazione;
- serie progressive;
- registrazione RIR, percezione, tecnica, dolore;
- calcolo carichi per range 10-12, 8-10, 6-8, 4-6;
- salvataggio profilo carichi esercizio.

## Milestone 6 — Trenino Invictus

Obiettivo: calcolare serie, reps e pesi della progressione Trenino e correggere i carichi.

Da fare:

- template progressione 6 step;
- step corrente per esercizio;
- generazione serie previste;
- suggerimento carico per range;
- correzione intra-sessione;
- avanzamento o ripetizione step;
- gestione sedute saltate.

## Milestone 7 — Statistiche e consigli

Obiettivo: visualizzare progressioni e produrre consigli spiegabili.

Da fare:

- andamento carichi;
- stima 1RM;
- volume e tonnellaggio;
- ripetizioni in riserva medie;
- aderenza allenamenti;
- sedute saltate;
- consigli motivati dai dati reali.

## Aggiornamento Step 2

Completato il primo blocco di navigazione del dominio: dashboard con conteggi reali, archivio attrezzature, dettaglio attrezzatura, configurazione strumenti disponibili in palestra, archivio esercizi, filtro esercizi disponibili e dettaglio esercizio.

Prossimo step consigliato: introdurre le schede di allenamento (`WorkoutPlan` e `WorkoutPlanExercise`) mantenendo separata la pianificazione dalla sessione reale.

## Aggiornamento Step 3

Completata la prima versione delle schede di allenamento. Sono disponibili elenco schede, creazione scheda, dettaglio scheda, aggiunta esercizi con prescrizione base e progressione per esercizio.

La Milestone 3 è da considerare avviata ma non completa: mancano ancora modifica scheda, riordino esplicito degli esercizi, duplicazione scheda e filtri sugli esercizi disponibili in base alla palestra durante l'aggiunta. Il prossimo blocco consigliato è il diario degli allenamenti reali, almeno nella forma minima: avvio sessione da scheda e registrazione serie effettive.

## Aggiornamento Step 4

Avviata la Milestone 4 con il diario allenamenti reali. È possibile partire da una scheda, generare una sessione, compilare le serie effettive e chiudere l'allenamento come completato o parziale in base alle serie registrate.

La Milestone 4 non è ancora completa: mancano sessioni libere, marcatura esplicita di esercizi saltati, modifica/cancellazione sessione, riepilogo più avanzato e prime regole di analisi dei carichi. Il prossimo blocco consigliato è consolidare la registrazione reale e poi introdurre la calibrazione iniziale come sessione dedicata.

## Aggiornamento Step 5

Consolidata la Milestone 4: il diario ora gestisce serie saltate, esercizi saltati e riepilogo sessione. Rimangono fuori da questo blocco sessioni libere, cancellazione/modifica avanzata della sessione e UI specializzata per tracking mode.

Il prossimo blocco consigliato è la calibrazione iniziale come sessione dedicata, riutilizzando `WorkoutSession`, `ExerciseSession` e `SetLog` invece di creare un diario parallelo.

## Aggiornamento Step 6

Prima di aggiungere calibrazione e progressione Trenino è stata migliorata la UI del diario allenamenti. La registrazione delle serie ora è mobile-first, a card, con campi principali adattati al tipo di esercizio. Questo riduce dispersione visiva e rende più realistico l'uso in palestra da smartphone.

Il prossimo step può tornare alla roadmap funzionale: calibrazione iniziale come sessione dedicata, riutilizzando il diario reale appena consolidato.

## Aggiornamento Step 7

Prima di procedere con calibrazione e Trenino, è stata uniformata la UI esistente usando come riferimento la pagina diario allenamento approvata. Le pagine principali ora sono più adatte all'uso da smartphone e mostrano le informazioni come card operative invece di tabelle dense.

Questo step non cambia il dominio. La prossima evoluzione funzionale resta la calibrazione iniziale come sessione dedicata, ma da qui in avanti ogni nuova pagina dovrà usare lo stesso linguaggio: card, sezioni, campi sempre visibili e azioni principali grandi.

## Aggiornamento Step 8

Avviata la calibrazione iniziale. Ora l'app può creare una sessione di calibrazione per un esercizio peso/reps, registrare le serie test nel diario e finalizzare un profilo carichi iniziale per i range 10-12, 8-10, 6-8 e 4-6.

La Milestone calibrazione non è ancora completa: mancano indicazioni più guidate sul peso da provare nelle serie test, ricalibrazione multi-esercizio in un'unica sessione, gestione dei profili storici in dettaglio e uso automatico del profilo per precompilare i carichi delle schede/Trenino. Il prossimo step consigliato è usare `ExerciseLoadProfile` per proporre i primi `targetWeightKg` nelle sessioni generate da scheda.

## Aggiornamento Step 11

Avviata l'integrazione reale del Trenino Invictus. Gli esercizi in scheda con progressione `Trenino Invictus` generano ora le serie dello step 1 nel diario: 2x8-10 e 2x10-12. Il sistema prova anche a usare il profilo carichi più recente dell'esercizio per valorizzare il carico suggerito della singola serie.

La prossima evoluzione della milestone Trenino è calcolare lo step corrente in modo persistente o derivato dallo storico. Servirà distinguere: seduta centrata, seduta troppo facile, seduta troppo pesante, step da ripetere, step da avanzare e step da ridurre dopo assenze lunghe.

## Aggiornamento Step 12

Avviato il calcolo dello step corrente del Trenino dallo storico. La generazione delle sessioni usa ora l'ultimo esito chiuso per decidere se produrre lo step successivo o ripetere lo step precedente. La valutazione viene mostrata anche nel diario allenamento.

Rimangono aperti: gestione pausa lunga/sedute saltate tra due allenamenti, correzione più fine del carico suggerito a parità di step, vista dedicata allo stato progressione per esercizio e spiegazione più dettagliata del prossimo step prima di avviare la sessione.

## Step 14 - Correzione carico operativa

Completato il primo livello di correzione intra-sessione: ogni serie registrata può produrre un suggerimento operativo di aumento, mantenimento, mantenimento prudente o riduzione del carico. Il prossimo miglioramento sarà usare queste correzioni per precompilare automaticamente le serie successive ancora aperte.

## Step 15 - Suggerimento operativo propagato

Completato il primo automatismo intra-sessione: dopo una serie salvata, la prossima serie compatibile riceve il carico suggerito aggiornato. Rimane da estendere la logica a fine allenamento per aggiornare il profilo carichi dell’esercizio.

## Step 17 - Chiarezza carico suggerito

Il diario distingue tra carico target ufficiale e carico suggerito dalla serie precedente. Questo rende coerente la UI durante l’allenamento anche prima di avere profili carichi completi o progressioni consolidate.

## Step 18 - Diario più operativo da smartphone

Il carico suggerito ora è direttamente utilizzabile tramite pulsante di copia nel campo peso. Il diario resta manuale e controllabile: l'utente copia il peso, può modificarlo e poi salva la serie.

## Step 19 - Diario: recupero previsto più operativo

Il diario consente ora di copiare rapidamente anche il recupero previsto nel recupero effettivo. Questo rende più veloce la registrazione da smartphone e prepara la futura gestione timer.

## Step 20 - Flusso guidato durante l'allenamento

Il diario ora guida l'utente verso la prossima serie aperta, riducendo il rischio di inserire dati nella riga sbagliata quando l'allenamento contiene molte serie.

## Step 21 - Flusso di compilazione sequenziale

Il diario guida ora in modo più lineare l'inserimento: salva o salta una serie, poi riparte dalla prossima serie aperta. Questo migliora l'uso su smartphone durante l'allenamento reale.

## Step 22 - Diario compatto durante l'allenamento

Il diario ora può mostrare solo le serie ancora da compilare. Questo riduce la densità della pagina quando l'allenamento contiene molte serie e completa il flusso guidato introdotto con la prossima serie evidenziata.

## Step 23 - CRUD dati manuali

Avviata la nuova area di lavoro per la gestione manuale dei dati. L'ordine deciso è: Attrezzature, Esercizi, Schede, Diario. Lo Step 23 implementa il CRUD delle Attrezzature come base di comportamento e stile da replicare sugli altri archivi.

## Step 24 - CRUD Esercizi

Completato il secondo blocco della gestione dati manuale. Dopo Attrezzature, anche Esercizi può essere creato, modificato ed eliminato dalla UI. Il prossimo CRUD naturale è Schede, con attenzione maggiore perché contiene righe ordinate e prescrizioni.

## Step 25 - CRUD Schede

Completato il terzo blocco della gestione dati manuale. Dopo Attrezzature ed Esercizi, anche le Schede sono ora modificabili ed eliminabili dalla UI. Resta il CRUD Diario per modificare/eliminare sessioni e gestire sessioni di test.

## Step 26 - CRUD Diario

Completato il quarto blocco della gestione dati manuale. Il Diario ora permette modifica dati sessione ed eliminazione di sessioni, utile per correggere errori e cancellare sessioni di test. I CRUD principali richiesti sono ora coperti: Attrezzature, Esercizi, Schede e Diario.

## Step 27 - Duplicazione Schede

Aggiunta duplicazione delle schede, utile per creare varianti senza reinserire ogni esercizio. Questo completa un miglioramento pratico previsto dopo il CRUD Schede.

## Step 28 - Rifinitura UI card azioni

Aggiunta correzione responsive globale per le azioni nelle card. Questo rende più robusti tutti i CRUD appena introdotti quando una card contiene più pulsanti.

## Step 29 - Rifinitura azioni CRUD

Le azioni distruttive nei CRUD vengono ora separate visivamente dalle azioni principali. Questo migliora usabilità e sicurezza su smartphone e desktop, soprattutto dopo l'aggiunta dei CRUD completi.

## Step 30 - Conferme distruttive coerenti

Le azioni pericolose ora usano una conferma modale coerente con l'interfaccia, evitando il dialog nativo del browser e rendendo più chiaro il flusso sui dispositivi mobili.

## Step 31A - Consolidamento CRUD: validazioni visive

Iniziato il consolidamento dei CRUD con validazioni visive sui form principali. Questo step rende più chiari gli errori di compilazione prima di proseguire con statistiche e nuove funzionalità.

## Step 31B - Consolidamento CRUD: conservazione input

I form CRUD conservano i dati digitati dopo errori di validazione. Questo completa la parte più importante della validazione visuale introdotta nello Step 31A e riduce il rischio di perdita dati durante la compilazione.

## Step 31C - Consolidamento CRUD: conferme specifiche

Le conferme distruttive ora indicano esplicitamente cosa verrà eliminato o rimosso. Questo riduce errori operativi nei CRUD appena introdotti e rende il comportamento più adatto all’uso reale.

## Step 31D - Consolidamento CRUD: validazioni righe scheda

Il consolidamento CRUD copre ora anche le righe esercizio delle schede. Questo riduce il rischio di creare allenamenti con serie vuote, range ripetizioni invertiti o progressioni incompatibili con il tipo di esercizio.

## Step 31E - Consolidamento CRUD: auto slug

Completato un ulteriore miglioramento di usabilità dei CRUD: lo slug viene suggerito automaticamente dal nome, riducendo errori e lavoro manuale senza togliere controllo all’utente.

## Step 32A - Statistiche base

Avviato il blocco statistiche/progressione. La prima versione è volutamente semplice e usa card e tabelle, senza grafici complessi. Serve a chiudere il ciclo scheda → diario → analisi e a preparare lo storico dettagliato per esercizio.

## Step 32B - Storico esercizio

Esteso il blocco statistiche con lo storico dettagliato del singolo esercizio. Questo rende utilizzabile la pagina Statistiche come punto di ingresso operativo per capire progressione, pesi recenti e comportamento dell’esercizio nel tempo.

## Step 32C - Filtri statistiche

Il blocco statistiche ora permette analisi per periodo e tipo sessione. Questo prepara i passaggi successivi: grafici per esercizio, analisi trend e aggiornamento assistito del profilo carichi.

## Step 32D - Andamento visuale esercizio

Aggiunto il primo livello di grafico nello storico esercizio. Per evitare dipendenze premature, l'andamento recente è renderizzato con HTML/CSS e dati preparati lato PHP: peso migliore per sessione, volume per sessione e confronto con la sessione precedente. Questo completa la prima iterazione delle statistiche visuali e prepara analisi più evolute come trend su finestre temporali, migliori stimate e aggiornamento assistito dei profili carichi.

## Step 32E - Miglior set stimato

Aggiunta una metrica di forza stimata nelle statistiche, utile per confrontare serie con peso e ripetizioni diverse. La stima usa una formula Epley prudente su peso, ripetizioni e RIR, senza nuove tabelle e senza cambiare il diario. La metrica compare nella pagina Statistiche, nello storico del singolo esercizio, nel riepilogo per sessione, nelle tabelle delle serie e nel grafico recente dell’esercizio.

## Step 32F - Aggregazione settimanale esercizio

Lo storico del singolo esercizio ora include una lettura settimanale. Le ultime settimane con dati registrati vengono aggregate senza nuove tabelle: sessioni, serie, ripetizioni, volume, miglior peso, miglior stimato e RIR medio sono calcolati dai riepiloghi sessione già prodotti dalla pagina statistiche. Questo rende più leggibile la progressione quando le sessioni aumentano e prepara eventuali analisi mensili o confronto tra periodi.

## Step 33A - Navigazione principale compatta

Avviato un passaggio di consolidamento UI sulla navigazione. Il menu alto non mostra più tutte le sezioni come voci separate, perché l'applicazione sta crescendo e la barra orizzontale non è più adatta a ospitare ogni funzione. Le voci sono state raggruppate per area funzionale, mantenendo l'accesso rapido alle pagine principali senza introdurre una sidebar o cambiare architettura.

## Step 34A - Revisione scientifica 1RM stimato

Completato un passaggio di precisione terminologica e metodologica sulle statistiche. Il miglior valore derivato viene ora presentato come `1RM stimato`, con esclusione delle serie troppo lunghe e marcatura delle stime meno affidabili. Questo rende la pagina Statistiche più corretta per l'uso bodybuilding reale, soprattutto quando convivono serie pesanti, serie di ipertrofia e lavori metabolici ad alte ripetizioni.

## Step 34B - Qualità della stima 1RM

Estesa la revisione scientifica del `1RM stimato` con una lettura esplicita della qualità delle stime. Le pagine statistiche ora indicano quante serie sono state considerate standard, quante indicative e quante escluse, evitando che l'utente interpreti un valore derivato come dato assoluto. Questo completa il primo passaggio di trasparenza sulle metriche di forza e prepara una futura guida interna su RM, 1RM stimato, RIR e cedimento tecnico.

## Aggiornamento dopo Step 35A

Il catalogo base ora è molto più ricco: il seed include 90 voci di attrezzatura e 100 esercizi derivati dagli elenchi forniti dall'utente. Il comando `app:catalog:seed` permette di portarli anche in un database esistente senza cancellare i dati operativi. Questo rende più utile la creazione manuale delle schede perché l'utente può scegliere direttamente molti esercizi comuni senza doverli inserire uno per uno.

Il prossimo passo consigliato non è aumentare ancora il numero di voci, ma migliorare la consultazione del catalogo: filtri per gruppo muscolare, tipo esercizio, attrezzatura disponibile e ricerca più comoda nelle pagine Esercizi e Attrezzature.

## Step 35B - Filtri catalogo esteso

Completato il primo miglioramento operativo dopo l'ampliamento del catalogo. Le pagine Attrezzature ed Esercizi non sono più semplici elenchi lunghi: ora includono filtri mirati e conteggio dei risultati. Anche l'aggiunta di un esercizio a una scheda è più gestibile grazie a una ricerca rapida nel select, senza introdurre una nuova UI complessa o dipendenze JavaScript.

Il prossimo passo potrà essere la gestione più comoda della disponibilità attrezzature rispetto al catalogo esteso, oppure una vista a gruppi/paginazione leggera se le liste continueranno a crescere.

## Step 35C - Sincronizzazione attrezzature palestra

Corretto il primo problema emerso con il catalogo esteso: `La mia palestra` deve rappresentare la disponibilità di tutte le attrezzature del catalogo, non solo delle righe già collegate al profilo. La pagina ora si auto-allinea al catalogo e il comando di seed aggiorna anche i collegamenti mancanti, rendendo coerenti catalogo, disponibilità attrezzature e filtri degli esercizi disponibili.

Il prossimo miglioramento naturale potrà essere l'aggiunta di filtri anche dentro `La mia palestra`, perché con l'intero catalogo visibile la pagina diventa corretta ma lunga.

## Step 35D - Esercizi disponibili nelle schede

Completato. La configurazione dell'attrezzatura presente/non presente ora influenza anche il pannello di aggiunta esercizio nelle schede. La vista resta permissiva: per impostazione predefinita mostra solo esercizi disponibili, ma l'utente può togliere il filtro e vedere l'intero catalogo, inclusi gli esercizi legati ad attrezzature non presenti.
