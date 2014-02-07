![Image](https://raw.github.com/uptimesoftware/uptime-metric-chart/master/img/logos/metric-chart-sm.png)
###Description
The Metric Chart gadget makes it possible to graph metrics collected by up.time in just a few clicks.  You can now finally visualize those important metrics on your custom dashboard in the form of beautiful line or area graphs!  As a bonus, we're also throwing in some additional dashboard layouts, well suited to housing your new graphs.

##New Features in v2.5
The new version of the Metric Chart now allows you to graph metrics for multiple elements on the same graph. Along with support for Network Device Metrics, and Retained Service Monitor Metrics that have been 'Saved For Graphing'.


###Version Compatibility
                        | 7.2 | 7.1 | 7.0 | 6.0 | 5.5 | 5.4 | 5.3 | 5.2 |
    --------------------|-----|-----|-----|-----|-----|-----|-----|-----|
      Metric Chart v2.5 | X   |     |     |     |     |     |     |     |    
      Metric Chart v2.0 | X   |     |     |     |     |     |     |     |
      Metric Chart v1.0 | X   |     |     |     |     |     |     |     |

###Known Limitations
By default, Metric Chart currently expects a standard Monitoring Station configuration, with the bundled MySQL database on either Windows or Linux.  However, it also supports Oracle Datastores via ODBC Drivers see the [readme for Oracle ODBC Driver Install Steps & Additional Details] (blob/master/src/stable/readme.txt)

###Upgrading from an earlier version of the Metric Chart
* Install the newest version of the .upk via the regular plugin manager process.
* Navigate to the Up.time Dashboards where your metric chart gadget is located. 
* Delete the existing gadgets using the Small Gear Icon
* Clear the Cache in your browser to ensure you don't have earlier versions of the php & javascript cached.
* Add a new Gadget and click on 'Refresh Gadgets' button to find the new version of the Metric Chart
* Choose your metrics & graph! 


###Candidate Features
* Trend lines
* Additional chart types (bar, pie, guage)
* Expand time span options
* Zooming
* _Your enhancement requests!_

###Advanced Instructions
* Open the configuration dialog by double-clicking on the chart
* Double-click on the eye icon to toggle verbose logging on and off

---

Thanks!

The up.time Team
support@uptimesoftware.com
