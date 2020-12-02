# coronadata
This project is meant to be an autoupdating, autocalculating, multi-datasource application to analyze the worldwide corona virus and COVID-19 situation, with special focus on Germany.

## PRE ALPHA STAGE
The project is a an early development stage. Do not use this for production environments.

## How it works
The project comes with two CLI scripts. One is used to configure the project and install (or overwrite) the database.
The other one is the updater itself. After you have configured your installation and the tool has installed the database successfully, you can use the updater as stand-alone-daemon, within a cron job, or as CLI one-shot solution.
In any case, the updater will automatically download its needed datasources (see below), stores them in compressed format within a cache directory and begins to calculate the results from any datasource.
The cache is held for a specified amount of time (by default 14400 seconds), after a new download is started. There is an override to force the download and an override to force the use of cache.

Anyway, after the download has finished, all the datasources are translated and stored in objects within memory. 
Before writing anything to the database, the hole calculation is done in memory and therefore a huge amount of memory is needed.

DO NOT TRY TO USE THIS TOOL WITH A SMALLER AMOUNT OF RAM THAN 16 GB!
THIS PROBABLY WILL SEND YOUR BOX INTO SWAPPINESS HELL.

Why we do this? Why we do not use the MySQL for storing and calculating.
Because we want the calculations to be done as fast as possible. In the first versions we tried to use the database for this job, but the calculation took several hours and this is not, what we prefer at this point.
Using the RAM speeded things up. While using the systems RAM, the calculation now take some few minutes and not hours as before. Problem using the RAM method, all datasets must be held in RAM and so it is blown up to more than 13 GB (for now, 2020-12-02).

After the calculations are done, the updater begins to store the calculated data to their corresponding MySQL tables.
To make things go ahead fast, we use transactions to insert (or update) the data. 
Anyway, the dataset and testresult store (with more than 500.000 rows for now) will take more than an hour to save (depending on the machine you use).

Because we were forced to use a hashing algorithm on the resultsets, the MySQL server must hash each dataset to "guarantee" unique entries. 
We don't hash the results within the calculation phase of the tool, because it took too long to do this. PHP is desgined for single thread use.
MySQL can do the same with multi-threading capabilities and is a quiet bit faster in hashing the results, so we do this while storing the data.
Admittedly, using MySQL to hash the resultsets will eat up the time advantage, using transactions. But there in summary, this method is the fastest for now. Believe me, we tried the others and the results were not good.

The result is a compromise, but we think a bit more than an hour for this complex calculations and the massive amount of data is a justifiable solution for us.

Running the updater as a daemon, the updated will download the datasources (or use the cache), calculate the results, merging all datasource in a sense making way and than waits for a specified amount of time, before it repeats.
Running the updater with the cron option, it will do nearly the same, but exits after it has stored its data. So it can be used in cronjobs, too.

After anything is done, you can use the grafana view to make things visible in a dashboard. The project comes with an example dashboard. 

### Requirements
To use this program you need the following resources:
- *** Linux box *** with 16 GB of RAM and at least 4 GB of free disk space
- *** MySQL database server ***
- *** PHP 7 with CLI and MySQLi extension ***

### Optional
To make things visible the database comes with grafana view. Therefore you need:
- *** A webserver (like Apache2, NGINX) *** capable of runnning PHP and Grafana
- *** Grafana server *** [DOWNLOAD PAGE](https://grafana.com/grafana/download)

### Questions
Q: Why not just download the datasources, store them into different tables and do some joins to get the results?
A: Because there is a significant amount of missing things within the datasources. You will not get the results, you probably want. And the goal is to get a realistic overview of the situation and now the pre-chewed stuff from some "sources".
Q: Why slowing down the datasets and testresults store, while using a hashing algorithm?
A: The problem is, that some data comes without unique identifiers. Some do have "presumptive unique identifiers", but changes after a couple of days, which leads to duplicate entries and misscalculations. The only way to check, if a dataset is unique, is to hash its content. Therefore, this is unfortunatly needed.
Q: Why using multiple sources?
A: Any source has its benefits and disadvantages. For example, if you want to calculate the positive cases per square-kilometer (to get a sense making incidence), you need additional informations.
Q: Why using the DESTATIS database?
A: For many calculations we need the population of districts and states within germany, splitted by genders. This allows you to do some really cool and interesting stuff.
Q: Why it takes so long to build such things?
A: Each test takes more than an hour. Also, due to the global situation, we cannot work hours and days on things, nobody pays for. We have familys, too. So this can be done in our spare times and not as a fulltime job, sorry.
Q: Why not using more data from DESTATIS and do some more calculations?
A: This is planned, but for now, we must bring the project out of alpha stage into a more stable level.
Q: Why there is an oldcast table?
A: The oldcast will store any changes the RKI made within already stored datasets. This will allow you to track, what RKI (or someone else) has done "to adjust" its nowcasting data. Each change to nowcasts table will lead to dataset copy of the previous dataset in oldcasts table.

## How to use
After you have prepared your system, download the project from this page. Also, you need a registered account at DESTATIS.
After unpacking and changing the directory into projects root, do the following:

'''
sbin/CoronaData-Configure.php
'''

This will configure the database, your DESTATIS account and installs the MySQL stuff.

Now, you should be ready for the first shot:

'''
sbin/CoronaData-Updated.php cron
'''

Which will do a one-shot download and stores the calculated results within the MySQL.
If anything went well, start the updater on next day.

### What are the switches
Because of the massive amount of changes within the code, introducing the switches doesn't make sense for now.
If you would like to know, what switches you can use, just open the code. It should be easy to find for anybody who knows what to do.
Sorry for that, but this is a result of unpaid work :-)

## Credits
This project makes use of the following datasources:
- [EU Opendata for Covid-19](https://opendata.ecdc.europa.eu/covid19/casedistribution/json/)
- [RKI Testresults](https://opendata.arcgis.com/datasets/dd4580c810204019a7b8eb3e0b329dd6_0.geojson)
- [RKI Nowcasting](https://www.rki.de/DE/Content/InfAZ/N/Neuartiges_Coronavirus/Projekte_RKI/Nowcasting_Zahlen_csv.csv?__blob=publicationFile)
- [RKI Report feed](https://www.rki.de/SiteGlobals/Functions/RSSFeed/RSSGenerator_nCoV.xml)
- [DIVI Register](https://diviexchange.blob.core.windows.net/%24web/DIVI_Intensivregister_Auszug_pro_Landkreis.csv)
- [Genesis database by DESTATIS](https://www-genesis.destatis.de/genesisWS/rest/2020/)
