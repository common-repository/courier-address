jQuery(document).ready(function initialise_courier_address_lookups() {
  /* 
   * @author: Annesley Newholm
   * @documentation: https://developers.google.com/maps/documentation/javascript/places-autocomplete#add_autocomplete
   */
  jQuery('.wpcf7-courier-address').each(function() {
    var oAutoComplete;
    var aSouthWest, aNorthEast;
    var sSouthWest, sNorthEast, sType, bStrict;
    var jThis   = jQuery(this);
    var oBounds = courier_address_addressBounds();

    //prevent return key submissions
    jThis.closest('form').on('keydown', function(event) {
      var bIsReturn = (event.which == 13);
      if (bIsReturn) {
        event.preventDefault();
        event.stopImmediatePropagation();
      }
      return !bIsReturn;
    });

    //Options localized in PHP call
    if (window.courier_address_settings) {
      bStrict    = (courier_address_settings.courier_address_bounds_strict == 'on');
      sType      = courier_address_settings.courier_address_type;
      if (!sType) sType = "geocode";
    }
    
    //clear the associated inputs when box is cleared
    //place_changed does not trigger when box text is changed
    jThis.keyup(function(){
      if (!jThis.val()) jThis.siblings("input").val("").trigger("change");
    });
    
    oAutoComplete = new google.maps.places.Autocomplete(
        jThis.get(0), {
          types:  [sType],
          bounds: oBounds,
          strictBounds: bStrict, //never suggest anything from outside the bounds
          //componentRestrictions: {country: 'hu'},
    });
    
    oAutoComplete.addListener('place_changed', function() {
      var sComponentType, sPostCode, sPostCodeCharge, jInput, oPlace = oAutoComplete.getPlace();
      var i, sPostCodeGroup, sPostCodeComma, sType;
      var aMinimumTypeLevelRequiredMatches, iMinimumTypeLevelRequired = 0;
      var sClass = jThis.attr('class');
      
      //classes
      //do this first so that layout changes for the map are correct
      jThis.addClass("courier-address-has-address").parents().addClass("courier-address-has-address");

      //pre-clear
      jThis.siblings("input").val("").trigger("change");
      jThis.closest("form").removeClass("sent");
      
      //fill out all associated detail inputs
      //e.g. postal_code => next input.postal_code
      //see documentation above for all possible values
      if (oPlace && oPlace.address_components) {
        if (window.console) window.console.log(oPlace);
                              
        if (oPlace.types && oPlace.types.length) sType = oPlace.types[0];
        if (aMinimumTypeLevelRequiredMatches = sClass.match(/required-address-detail-(\d+)/))
          iMinimumTypeLevelRequired = parseInt(aMinimumTypeLevelRequiredMatches[1]);
                              
        //address parts
        for (var i = 0; i < oPlace.address_components.length; i++) {
          oAddressComponent = oPlace.address_components[i];
          sComponentType    = oAddressComponent.types[0];
          jInput            = jThis.siblings("input." + sComponentType);
          jInput.val(oAddressComponent.long_name).trigger("change");
          if (sComponentType == "postal_code") sPostCode = oAddressComponent.long_name;
        }
      
        //Budapest postcode district
        if (sPostCode && sPostCode.length > 3) {
          sDistrict = sPostCode.substr(1,2);
          jInput = jThis.siblings("input.district");
          jInput.val(sDistrict).trigger("change");
          sType = 'postcode';
        }
        
        //PostCode group charge
        if (sPostCode && window.courier_address_settings) {
          sPostCodeComma = ',' + sPostCode + ',';
          i = 1;
          while (sPostCodeGroup = courier_address_settings['courier_address_postcode_group' + i]) {
            sPostCodeGroup = ',' + sPostCodeGroup.replace(/\s*,\s*/g, ',') + ',';
            if (sPostCodeGroup.indexOf(sPostCodeComma) !== -1) break;
            i++;
          }
          if (sPostCodeGroup) {
            sPostCodeCharge = courier_address_settings['courier_address_postcode_group_price' + i];
            jThis.siblings("input.postal_code_price").val(sPostCodeCharge);
            sType = 'postcode_charge';
          }
        }
        iTypeLevel = courier_address_type_level(sType);
        
        //immediate validation
        //if postcode_charge is required and no valid postcode was available then show the postcode required first
        if (iMinimumTypeLevelRequired == 140 && iTypeLevel < 130) iMinimumTypeLevelRequired = 130;
        //remove current form submission tips
        jThis.parent().children(".wpcf7-not-valid-tip").remove();
        jThis.parent().removeClass("wpcf7-not-valid")
          .removeClass("courier-address-level-not-valid")
          .removeClass(function (iIndex, sClassName) {
            return (sClassName.match(/courier-address-level-.*-not-valid/g) || []).join(' ');
          });
        if (iTypeLevel < iMinimumTypeLevelRequired) {
          jThis.parent().addClass("wpcf7-not-valid")
            .addClass("courier-address-level-not-valid")
            .addClass("courier-address-level-" + iMinimumTypeLevelRequired + "-not-valid");
          jThis.siblings(".type-validates").val("");
        } else {
          //type-validates is always required, but always completed if satisfied
          jThis.siblings(".type-validates").val(iTypeLevel);
        }

        //misc values
        jThis.siblings("input.type").val(iTypeLevel).trigger("change");
        jThis.siblings("input.lat").val(oPlace.geometry.location.lat()).trigger("change");
        jThis.siblings("input.lng").val(oPlace.geometry.location.lng()).trigger("change");
      }
    });
  });
});

var ocourier_address_map, acourier_address_map_items = [];
jQuery(document).ready(function initialise_distance_calcs() {
  jQuery(".courier-address-data").change(function() {
    //can update the distance field using pythagarus
    var fDistance, gFrom, gTo, fDistanceKM, gFromPoint, gToPoint, oMap;
    var jThis      = jQuery("input.wpcf7-courier-address-distance"); 
    var sAddress1  = jThis.siblings(".courier-address1").val();
    var sAddress2  = jThis.siblings(".courier-address2").val();
    var sMeasure   = jThis.siblings(".measure").val();
    var sMapHeight = jThis.siblings(".map-height").val();
    var jMap       = jThis.siblings(".courier-address-distance-map-canvas");
    var sFromLat   = jQuery("input.courier-address-data.lat." + sAddress1).val();
    var sFromLng   = jQuery("input.courier-address-data.lng." + sAddress1).val();
    var sToLat     = jQuery("input.courier-address-data.lat." + sAddress2).val();
    var sToLng     = jQuery("input.courier-address-data.lng." + sAddress2).val();
    
    //distance calculation
    if (sFromLat && sFromLng && sToLat && sToLng) {
      gFrom = new google.maps.LatLng(sFromLat,sFromLng);
      gTo   = new google.maps.LatLng(sToLat,sToLng);
      fDistanceKM = gDistance(gFrom, gTo) / 1000;
      jThis.addClass("courier-address-has-distance").parents().addClass("courier-address-has-distance");
      jThis.val((parseInt(fDistanceKM * 10) / 10) + sMeasure).trigger("change");
    }

    //show map
    if (jMap.length) {
      if (sFromLat && sFromLng) gFromPoint = new google.maps.LatLng(sFromLat,sFromLng);
      if (sToLat && sToLng)     gToPoint   = new google.maps.LatLng(sToLat, sToLng);
      if (gFromPoint || gToPoint) {
        if (!window.ocourier_address_map) {
          //show the map
          //set heights and classes first so that map area is correct
          if (!sMapHeight) sMapHeight = "400";
          jMap.height(parseInt(sMapHeight));
          jThis.addClass("courier-address-has-map").parents().addClass("courier-address-has-map");

          //create the map and points
          ocourier_address_map = new google.maps.Map(jMap.get(0), {
            zoom: 13, 
            center: new google.maps.LatLng( 47.4811277,18.9898761 ), //Budapest
            streetViewControl: false,
            zoomControl:  false,
            scrollwheel:  false,
            scaleControl: false,
            mapTypeControl: false
          });
        } else {
          //remove current geometry
          for (var i=0; i < acourier_address_map_items.length; i++) 
            acourier_address_map_items[i].setMap(null);
          acourier_address_map_items = [];
        }
        
        if (gFromPoint) {
          acourier_address_map_items.push(new google.maps.Marker({         
            position:gFromPoint,
            map:     ocourier_address_map,
          }));
          ocourier_address_map.setCenter(gFromPoint);
        }
        
        if (gToPoint)   {
          acourier_address_map_items.push(new google.maps.Marker({         
            position:gToPoint,
            map:     ocourier_address_map           
          }));
          ocourier_address_map.setCenter(gToPoint);
        }
        
        if (gToPoint && gFromPoint) { 
          acourier_address_map_items.push(new google.maps.Polyline({
            path:         [gFromPoint, gToPoint],
            geodesic:     true,
            strokeColor:  '#FF0000',
            strokeOpacity: 1.0,
            strokeWeight: 2,
            map:          ocourier_address_map
          }));
          ocourier_address_map.fitBounds(courier_address_createBounds(gFromPoint, gToPoint));
        }
      }
    }
  });
});

jQuery(document).ready(function initialise_result_calcs() {
  jQuery(".equation_component").change(function(){
    jQuery("input.wpcf7-result-container").each(function(){
      var fPrice, fPriceRounded;
      var jThisResultContainer = jQuery(this);
      var sEquation    = jThisResultContainer.siblings(".equation").text();
      var sExplanation = jThisResultContainer.siblings(".explanation").text();
      var sCurrency    = jThisResultContainer.siblings(".currency").val();
      
      //compile options
      jQuery(".equation_component").each(function(){
        var jThis = jQuery(this);
        var sName = jThis.attr("name");
        var sReplaceName = "{" + sName + "}";
        var sVal  = jThis.val();
        if (jThis.is("input[type=checkbox]") && !jThis.attr("checked")) sVal = "1";
        if (sVal != "") {
          sEquation    = sEquation.replace(sReplaceName, parseFloat(sVal));
          sExplanation = sExplanation.replace(sReplaceName, parseFloat(sVal));
        }
      });
    
      //evaluate equation
      if (sEquation.indexOf('{') === -1) {
        fPrice        = eval(sEquation);
        fPriceRounded = parseInt(fPrice * 100) / 100;
        sExplanation  = sExplanation.replace("{result}", fPriceRounded);
        
        //update outputs
        jThisResultContainer.addClass("courier-address-has-result").parents().addClass("courier-address-has-result");
        jThisResultContainer.siblings(".result-container-value").text(fPriceRounded + sCurrency);
        jThisResultContainer.siblings(".result-container-explanation").val(sExplanation).text(sExplanation);
        jThisResultContainer.val(fPriceRounded);
      } else {
        //if (window.console) window.console.warn("equation [" + sEquation + "] still has {parameters}");
        jThisResultContainer.removeClass("courier-address-has-result").parents().removeClass("courier-address-has-result");
        jThisResultContainer.siblings(".result-container-value").text("");
        jThisResultContainer.siblings(".result-container-explanation").val("").text("");
        jThisResultContainer.val("");
      }
    });
  });
});

if (!window.rad) {
  function rad(x) {
    return x * Math.PI / 180;
  }
}

if (!window.gDistance) {
  function gDistance(p1, p2) {
    var R       = 6378137; //Earth’s mean radius in meter
    var dLat    = rad(p2.lat() - p1.lat());
    var dLong   = rad(p2.lng() - p1.lng());
    var a       = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
      Math.cos(rad(p1.lat())) * Math.cos(rad(p2.lat())) *
      Math.sin(dLong / 2) * Math.sin(dLong / 2);
    var c       = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    var iMeters = R * c;
    return iMeters;
  };
}

function courier_address_type_level(sType) {
  //same as the PHP lookup
  var iLevel = 0;
  switch (sType) {
    case 'route':            iLevel = 110; break;
    case 'street_address':   iLevel = 120; break;
    case 'postcode':         iLevel = 130; break;
    case 'postcode_charge':  iLevel = 140; break;
  }
  return iLevel;
}


function courier_address_createBounds(oPoint1, oPoint2) {
  //need to create by sw, ne
  var fMinLat = Math.min(oPoint1.lat(), oPoint2.lat());
  var fMaxLat = Math.max(oPoint1.lat(), oPoint2.lat());
  var fMinLng = Math.min(oPoint1.lng(), oPoint2.lng());
  var fMaxLng = Math.max(oPoint1.lng(), oPoint2.lng());
  var oSW = new google.maps.LatLng(fMinLat, fMinLng);
  var oNE = new google.maps.LatLng(fMaxLat, fMaxLng);
  return new google.maps.LatLngBounds(oSW, oNE);
}

function courier_address_addressBounds() {
  var aSouthWest, aNorthEast;
  var sSouthWest, sNorthEast;
  var oBounds;

  if (window.courier_address_settings) {
    sSouthWest = courier_address_settings.courier_address_bounds_southwest;
    sNorthEast = courier_address_settings.courier_address_bounds_northeast;
  }
  
  if (sSouthWest && sNorthEast) {
    aSouthWest = sSouthWest.split(',');
    aNorthEast = sNorthEast.split(',');
    oBounds = courier_address_createBounds( //Budapest SW - NE
      new google.maps.LatLng(parseFloat(aSouthWest[0]), parseFloat(aSouthWest[1])),
      new google.maps.LatLng(parseFloat(aNorthEast[0]), parseFloat(aNorthEast[1]))
    );
  }
  return oBounds;
}
