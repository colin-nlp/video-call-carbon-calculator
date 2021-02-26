<?php    
  // create short variable names
  $callType = $_GET['callType'];
  $participants = $_GET['participants'];
  $duration = $_GET['duration'];
  $callQuality = $_GET['callQuality'];
  $country = $_GET['country'];
  $distance = $_GET['distance'];

  // Run Calcs
  $carbon = 0;
  $callweight = 0;
  $energy = 0;
  $totaldata = 0;
  $distance = 0;
    switch ($callQuality){
        //Call weights are based on required bandwidth from Zoom - https://support.zoom.us/hc/en-us/articles/201362023
        //1 Gigabyte = 8589934592 bits, 1 kb = 1024bits and 1Mb = 1048576 bits
        case "sd" :
            if ($callType == "conference" && $participants > 2) {
                //Convert 1.2Mbps up and 600kbps down to gigabytes per second
                $callWeight = ((1.2 * 1048576) + (600 * 1024))/8589934592;
            } else {
                //Convert 600kbps (up and down) to gigabytes per second
                $callWeight = (2 * (600 * 1024))/8589934592;
            }
            break;
        case "voice" :
            //Convert 80kbps (up and down) to gigabytes per second
            $callWeight = (2 * (80 * 1024))/8589934592;
            break;
        case "hd" :
            if ($callType == "conference" && $participants > 2) {
                //Convert 2.6Mbps up and 1.8Mbps down to gigabytes per second
                $callWeight = ((2.6 * 1048576) + (1.8 * 1048576))/8589934592;
            } else {
            //Convert 1.2Mbps (up and down) to gigabytes per second
            $callWeight = (2 * (1.2 * 1048576))/8589934592;
            }
            break;
        case "vhd" :
            if ($callType == "conference"){
                //Convert 3.8Mbps up and 3.0Mbps down to gigabytes per second
                $callWeight = ((3.8 * 1048576) + (3.0 * 1048576))/8589934592;
            } else {
                //For a webinar the extra upload weight for the host has been ignored for simplicity
                $callWeight = (2 * (3.0 * 1048576))/8589934592;
            }
            break; 
    };
    switch ($country){
        //Energy carbon intensity per country - https://www.rensmart.com/Calculators/KWH-to-CO2
        //Except USA which is via https://www.sciencedirect.com/science/article/abs/pii/S0921344920307072
        //Values are CO2 per kWh
        case "uk" :
            $energy = 268;
            break;
        case "eire" :
            $energy = 392;
            break;
        case "france" :
            $energy = 67;
            break;
        case "eu" :
            $energy = 294;
            break;
        case "usa" :
            $energy = 499;
            break; 
        case "other" :
            $energy = 475;
            break; 
    };
    switch ($callType) {
        //Calculated carbon output is equal to 0.077kWh/GB * CO2pkWh * (people in call * call length in secs * GB/s transfer of call)
        //For a webinar the GB/s transfer is halved as only downloading data (except host, who is only uploading)
        //0.07kWh/GB is from https://www.sciencedirect.com/science/article/abs/pii/S0921344920307072
        case "webinar" :
            $totaldata = ($participants * ($duration * 3600) * ($callWeight / 2));
            $carbon = 0.07 * $energy * ($participants * ($duration * 3600) * ($callWeight / 2));
            break;
        case "conference" :
            $totaldata = ($participants * ($duration * 3600) * ($callWeight / 2));
            $carbon = 0.07 * $energy * ($participants * ($duration * 3600) * $callWeight);
            break;
    };
?>

<!DOCTYPE html>
<html>
  <head>
   <title>
       <?php
        if ($carbon > 0) {
            if ($carbon < 1000) {
                echo 'My Conference call emits '.number_format($carbon,1).'gCO2e - No Lesser Panda';
            } else {
                echo 'My Conference call emits '.number_format(($carbon/1000),0).'kgCO2e - No Lesser Panda';
            };
        } else {
                echo 'Video Conference Carbon Calculator - No Lesser Panda';
            };
        ?>
   </title>

    <link href="style.css" rel="stylesheet" type="text/css" />
  </head>
  <body>
      
    <div id="results">
    <?php 
        //Calculate the equivalent distance in a car, average gCO2 per km from https://www.buyacar.co.uk/cars/209/car-tax-rates
        $distance = $carbon/127.9;
        if ($carbon > 0) {
            if ($carbon < 1000) {
                echo '<h2>This call would produce '.number_format($carbon,1).'gCO<sub>2</sub>e!</h2>';
            } else {
                echo '<h2>This call would produce '.number_format(($carbon/1000),1).'kgCO<sub>2</sub>e!</h2>';   
            };
            if ($distance < 1) {
                $distancemeters = $distance * 1000;
                echo '<p>That\'s the equivalent of '.number_format($distancemeters,0).'m in an average car.</p>';
            } else {
                echo '<p>That\'s the equivalent of '.number_format($distance,1).'km in the average car.</p>';
            };
            echo '<p>Total data transfered '.round($totaldata,2).'GB!</p>';
      };
    ?>
    </div>
      
    <form action="testcalculator.php" method="get">
    
    <div class="calc-grid">
        
        <!-- Call type selector -->  

        <div class="calc-grid-item">
            <h3>Select the Call Type:</h3>
        </div>
        <div class="calc-grid-item">
            <select name="callType" style="width:100%">
                <option value="conference"<?php if (isset($callType) && $callType=="conference") echo ' selected';?>>Conference Call</option>
                <option value="webinar" <?php if (isset($callType) && $callType=="webinar") echo ' selected';?>>Webinar</option>
            </select>
        </div>
        <div class="calc-grid-item">
            <p class="notes">Conference call represents a group call where everyone can speak, webinar represents a broadcast where only the host talks and displays a video or shares a screen and all other participants simply recieve content.</p>
        </div>

        <!-- Number of participants input -->    

        <div class="calc-grid-item">
            <h3>Number of Participants:</h3>
        </div>
        <div class="calc-grid-item">
            <input type="number" name="participants" size="20" max="10000" min="2" value="<?php echo $participants ?>" placeholder="Number of people"/>
        </div>
        <div class="calc-grid-item">
            <p class="notes">Minimum of 2 and maximum of 10,000</p>
        </div>

        <!-- Call Duration input -->

        <div class="calc-grid-item">
            <h3>Call Duration (Hours):</h3>
        </div>
        <div class="calc-grid-item">
            <input type="number" step=".01" name="duration" size="20" max="1000" min="0.01" value="<?php echo $duration ?>" placeholder="Call duration"/>
        </div>
        <div class="calc-grid-item">
            <p class="notes">Accepts decimals, minimum 0.01 and maximum 1,000.</p>
        </div>

        <!-- Video quality selector -->

        <div class="calc-grid-item">
            <h3>Select the Video Type:</h3>
        </div>
        <div class="calc-grid-item">
            <select name="callQuality" style="width:100%">
                <option value="sd" <?php if (isset($callQuality) && $callQuality=="sd") echo ' selected';?>>Standard Video</option>
                <option value="voice" <?php if (isset($callQuality) && $callQuality=="voice") echo ' selected';?>>Voice Only</option>
                <option value="hd" <?php if (isset($callQuality) && $callQuality=="hd") echo ' selected';?>>720p HD Video</option>
                <option value="vhd" <?php if (isset($callQuality) && $callQuality=="vhd") echo ' selected';?>>1080p HD Video</option>
            </select>
        </div>
        <div class="calc-grid-item">
            <p class="notes">The standard video type is the default setting for Zoom or Teams.</p>
        </div>

        <!-- Participants location selector -->

        <div class="calc-grid-item">
            <h3>Select the Location of Participants:</h3>
        </div>
        <div class="calc-grid-item">
            <select name="country" style="width:100%">
                <option value="uk" <?php if (isset($country) && $country=="uk") echo ' selected';?>>United Kingdom</option>
                <option value="eire" <?php if (isset($country) && $country=="eire") echo ' selected';?>>Republic of Ireland</option>
                <option value="france" <?php if (isset($country) && $country=="france") echo ' selected';?>>France</option>
                <option value="eu" <?php if (isset($country) && $country=="eu") echo ' selected';?>>EU Average</option>
                <option value="usa" <?php if (isset($country) && $country=="usa") echo ' selected';?>>USA</option>
                <option value="other" <?php if (isset($country) && $country=="other") echo ' selected';?>>World Average</option>
            </select>
        </div>
        <div class="calc-grid-item">
            <p class="notes">Alters the carbon intensity of power used for the data transfer. If participants are not in the same country then the world or EU averages can be used.</p>
        </div>
        <div class="calc-grid-item">
            <input type="submit" value="Calculate" />
        </div>      
    </div>
    
    </form>
    
    <div>
        <h2>About this Calculator</h2>
        <p>This calculator is designed to provide an estimate as to the amount of carbon produced from a call via a video conferencing app such as Zoom, Skype or Teams. The results are indicative only, the methodology and assumptions are described below.</p>
        <h3>How the Calculator Works</h3>
        <p>Estimations for the amount of carbon emitted from each call are based on the following equation:</p>
        <p>CF = C.P.(n.t.(D<sub>u</sub>+D<sub>d</sub>))</p>
        <p>Where:</p>
        <ul>
            <li>CF = Total carbon footprint of the call (gCO<sub>2</sub>e)</li>
            <li>C = Carbon intensity of power generation (gCO<sub>2</sub>e/kWh)</li>
            <li>P = Power required to transfer 1 GB of data (kWh/GB)</li>
            <li>n = Number of participants on the call</li>
            <li>t = Length of call (seconds)</li>
            <li>D<sub>u</sub> = Data upload rate based on the type of call (GB/s)</li>
            <li>D<sub>d</sub> = Data download rate based on the type of call (GB/s)</li>
        </ul>
        <p>This equation takes the total amount of time of the call and multiplies it by the number of particiapnts on the call and the combined data upload and download rates. This estimates the total amount of data transfered between the participants during the call. The value of data transferred is then multiplied by an energy intensity value for each GB of data transferred and finally this value is multiplied by the average carbon intensity of each kWh.</p>
        <h3>Calculation Scope</h3>
        <p>The calculation only covers the carbon emmissions caused by the transfer of data during the call, it does not take into account the the energy used by the devices that the call participants are using. The energy usage of the device can be larger than the energy usage of the call itself [1], however it is highly dependent on the device. Using a smartphone is more efficient than a widescreen desktop, but the variance between devices can be large.</p>
        <p>The calculation does not account for the carbon emmissions associated with the water usage of data centers. Some data centers use waters to provide cooling for the servers, the volume of water used by these centers can be significant [2]. Pumping, treating and disposing of this water all has associated carbon emmissions that are not dealt with here.</p>
        <h3>Data and Uncertainties</h3>
        <p>The following data is used as part of the calculation.</p>
        <h4>Carbon Intensity of Electricty Production</h4>
        <table>
            <tr>
                <td>Country</td><td>gCO<sub>2</sub>e/kWh</td>
            </tr>
            <tr>
                <td>United Kingdom</td><td>268 [3]</td>
            </tr>
            <tr>
                <td>Rep of Ireland</td><td>392 [3]</td>
            </tr>
            <tr>
                <td>France</td><td>67 [3]</td>
            </tr>
            <tr>
                <td>EU Average</td><td>294 [3]</td>
            </tr>
            <tr>
                <td>USA</td><td>499 [2]</td>
            </tr>
            <tr>
                <td>World Average</td><td>475 [4]</td>
            </tr>
        </table>
        <p>These values are representative average values, they are predominantly year averages from 2017 data provided by the EEA.  The value can shift per day and by region of the country. For example in days of low wind the power provide by renewable wind energy in the UK drops and the short fall is made up by burning extra gas or coal, thus increasing the carbon output.</p>
        <h4>Energy Intensity of Data Transfer</h4>
        <p>This calculator uses a value of 0.07kWh/GB, this is based on the 2021 paper by Obringer et al. [2] which in turn is based on calculations performed by Kamiya [1]. There is debate about how to quantify the energy intensity of data transfer, a widely quoted report by the Shift Project [4] uses much higher energy intensity rates than this value, although the Shift Project have stated that this energy intensity rate may not be suitable for calculating high bit rate activities such as streaming [5]. Another more conservative intensity rate is provided by the Website Carbon Calculator [6].In an online talk Malmodin, who has written a number of papers on the subject [7] has suggested that an energy per byte ratio is not suitable and energy consumption rates should be time dependent [8].</p>
        <p>For the sake of simplicity this calculation assumes that all data transferred is via wired broadband, energy intensity rate for mobile data such as 5G would be higher [1].</p>
        <h4>Data Transfer Rates</h4>
        <p>The data transfer rates are based on the system requirements listed on the Zoom website [9], these figures are representative of the transfer rates of other services such as Skype and Teams. Note that these are minimum requirements so actual transfer rates may be higher during a call.</p>
        <h4>Car Emmissions</h4>
        <p>The distance traveled by an average car calculation is based on diving the total CO<sub>2</sub> of the call by the average gCO<sub>2</sub>/km of cars in the UK. The average value used is 127.9gCO<sub>2</sub>/km [10].</p>
        <h3>Comments, Corrections and Feedback</h3>
        <p>Please get in touch if you would like more information about the calculator, if you would like to request any additional features or you have any feedback or corrections. You can contact me via this <a href="https://www.nolesserpanda.com/pages/contact.php">contact form</a> or by sending an email to <a href="mailto:hello@nolesserpanda.com">hello@nolesserpanda.com</a>.</p>
        <h3>References</h3>
        <ol>
            <li><a href="https://www.carbonbrief.org/factcheck-what-is-the-carbon-footprint-of-streaming-video-on-netflix">Factcheck: What is the carbon footprint of streaming Netflix - G Kamiya, 2020</a></li>
            <li><a href="https://www.sciencedirect.com/science/article/abs/pii/S0921344920307072">The overlooked environmental footprint of increasing Internet use - R Orbinger et al, 2021</a></li>
            <li><a href="https://www.eea.europa.eu/data-and-maps/data/co2-intensity-of-electricity-generation">CO<sub>2</sub> Intensity of Electricity - EEA 2017</a></li>
            <li><a href="https://theshiftproject.org/en/article/lean-ict-our-new-report/">Lean ICT: Towards digital sobriety - The Shift Project 2019</a></li>
            <li><a href="https://theshiftproject.org/en/article/shift-project-really-overestimate-carbon-footprint-video-analysis/">Did the Shift Project really overestimate the carbon footprint of online video? - The Shift Project 2020</a></li>
            <li><a href="https://www.websitecarbon.com/how-does-it-work/">Website Carbon - Wholegrain Digital</a></li>
            <li><a href="https://www.ericsson.com/4a9f28/assets/local/news/2016/09/energy-and-carbon-footprint-ict-em-sector-sweden-1990-2015.pdf">The energy and carbon footprint of the ICT and E&amp;M sector in Sweden 1990-2015 and beyond - J Malmodin et al - 2015</a></li>
            <li><a href="https://youtu.be/Xo0PB5i_b4Y?t=1830">Science &amp; Society Forum: Växande IKT-sektor och fler datacenter – hur påverkas elförsörjningen? -  Kungl. Ingenjörsvetenskapsakademien IVA 2020</a></li>
            <li><a href="https://support.zoom.us/hc/en-us/articles/201362023">System Requirements - Zoom</a></li>
            <li><a href="https://www.which.co.uk/reviews/new-and-used-cars/article/car-emissions/car-co2-emissions-aRVNW9t0zLu6">Car CO2 Emmissions - Which? 2021</a></li>
        </ol>
    </div>  
    
  </body>
  </html>