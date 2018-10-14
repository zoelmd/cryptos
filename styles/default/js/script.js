window.reload_hooks = [];

function addReloadHook(h) {
    window.reload_hooks.push(h);
}

function runReloadHooks() {
    for(i=0;i<window.reload_hooks.length;i++) {
        var f = window.reload_hooks[i];
        //alert(window.reload_hooks.length);
        eval(f)();
    }
}

function pre_process_result(r) {

    if (r != undefined & r == 'login-is-needed') {
        $("#loginModal").modal('show');
        return  false;
    }

    if (isloggedin  == 0) {
        //show login modal
        $("#loginModal").modal('show');
        return true;
    }
    return false;

}

function show_notification_dropdown(t) {
    var container = $(".notification-dropdown");
    if (container.css('display') == 'none') {
        container.fadeIn();
        //$(t).find('notify')
        $.ajax({
            url :baseUrl + 'notification/load',
            success : function(c) {
                if (pre_process_result(c)) return false;
                container.find('.notification-lists').html(c);
            }
        })
    } else {
        container.fadeOut();
    }

    return false;
}
function my_confirm(f, m) {
    m = (m == undefined) ? window.lang.are_you_sure : m;
    swal({
      title: m,
      text: "",
      type: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: window.lang.yes,
      cancelButtonText: window.lang.cancel,
      confirmButtonClass: 'btn btn-confirm-ok',
      cancelButtonClass: 'btn btn-confirm-cancel',
      buttonsStyling: true
    }).then(f)
}

function showLoader() {
    $('.navbar-brand').hide();
    $(".header-loader-container").fadeIn();
}

function hideLoader() {
    $(".header-loader-container").fadeOut();

    if ($(window).width() > 700 ) $('.navbar-brand').show();
}


function validate_fileupload(fileName, type)
{
    var allowed_extensions = new Array("jpg","png","gif");
    allowed_extensions = supportImagesType.split(',');
    if (type == 'video') allowed_extensions = supportVideoType.split(',')
    var file_extension = fileName.split('.').pop().toLowerCase(); // split function will split the filename by dot(.), and pop function will pop the last element from the array which will give you the extension as well. If there will be no extension then it will return the filename.

    for(var i = 0; i <= allowed_extensions.length; i++)
    {
        if(allowed_extensions[i]==file_extension)
        {
            return true; // valid file extension
        }
    }

    return false;
}

function validate_image_size(input, type) {
    var files = input.files;
    if (type == 'image' && allowImagesUploaded != 0) {
        if (files.length > allowImagesUploaded) {
                notify(allowedImageError, 'error')
                $(input).val('');//yes
                return true;
        }
    }
    for(i = 0;i < files.length;i++) {
        var file = files[i];
        if (type == 'image') {
            if (!validate_fileupload(file.name, 'image')) {
                notify(window.lang.notImageError, 'error')
                $(input).val('');//yes
                return true;
            }
            if (file.size > allowPhotoSize) {
                //this file is more than allow photo file
                notify(allowImageSizeError, 'error');
                //empty the input
                $(input).val('');//yes
            }
        } else if (type == 'video') {
            if (!validate_fileupload(file.name, 'video')) {
                notify(window.lang.notVideoError, 'error')
                $(input).val('');//yes
                return true;
            }
            if (file.size > allowVideoSize) {
                //this file is more than allow photo file
                notify(allowVideoSizeError, 'error');
                //empty the input
                $(input).val('');//yes
            }
        } else if (type == 'both') {
            if (!validate_fileupload(file.name, 'image') && !validate_fileupload(file.name, 'video')) {
                  notify(window.lang.notStoryError, 'error')
                  $(input).val('');//yes
                  return true;
            } else {
                if (validate_fileupload(file.name, 'image')) {
                    if (file.size > allowPhotoSize) {
                        //this file is more than allow photo file
                        notify(allowImageSizeError, 'error');
                        //empty the input
                        $(input).val('');//yes
                    }
                }

                if (file.size > allowVideoSize) {
                    //this file is more than allow photo file
                    notify(allowVideoSizeError, 'error');
                    //empty the input
                    $(input).val('');//yes
                }
            }
        }
    }
}
function close_new_badge() {
        $('.new-badge-hover').remove();
        return false;
}


function reset_post_editor() {
    var editor = $("#post-editor");
    editor.find('.post-photo-input').val('')
    $('.emoji-input').val('')
    if ($(".emoji-input").data("emojioneArea") != undefined) $(".emoji-input").data("emojioneArea").setText("");
}

function delete_post(id) {
    my_confirm(function() {
        showLoader();
        $.ajax({
            url : baseUrl + 'post/delete',
            data : {id:id},
            success : function(r) {
                if (pre_process_result(r)) return false;
                notify(r, 'success');
                hideLoader();
                $("#each-post-" + id).remove();
            }
        })
    });
    return false;
}


function notify(m, type) {
    noty({
        text:m,
        type:type,
        progressBar : true,
        timeout:4000
    });
}

function do_report(type,id) {
    var modal = $("#reportModal");
    modal.modal("show");
    modal.find('#send-report-btn').click(function() {
            $(".loader-container").fadeIn();
            $.ajax({
                url : baseUrl + 'report/content',
                data : {type:type,id:id,text:modal.find('textarea').val()},
                success : function(r) {
                    if (pre_process_result(r)) return false;
                    modal.modal("hide");
                    $(".loader-container").fadeOut();
                    notify(r, 'success');
                    text:modal.find('textarea').val('')
                }
            })
    });

    return false;
}

function switch_exchange_currency(t) {
    var v = $(t).val();
    load_page(baseUrl+'exchanges?coin=' + v);
}

function subscribe_for_updates() {
    var socket = io.connect('https://streamer.cryptocompare.com/');
    var subscription = get_coin_on_page();
    socket.emit('SubAdd', { subs: subscription });
    if ($("#exchangeDataTableList").length > 0 || $("#tradeDataTableList").length > 0) {
        var fsym = $("#exchangeDataTableList").data('coin');
        if (fsym == undefined) fsym = $("#tradeDataTableList").data('coin');
        var tsym = base_currency;
        var dataUrl = "https://min-api.cryptocompare.com/data/subs?fsym=" + fsym + "&tsyms=" + tsym;
        showLoader();
        $.getJSON(dataUrl, function(data) {
            if ($("#exchangeDataTableList").length > 0) {
                currentSubs = data[base_currency]['CURRENT'];
                var  aa = currentSubs;

                for(i=0;i<aa.length;i++) {
                    var subsA = aa[i];

                    var spl = subsA.split('~');
                    $("#"+spl[1]+'-detail-container').show();
                    console.log("#"+spl[1]+'-detail-container');
                }
                        var count = 0;
                        $('.coin-exchange-container').each(function() {
                            if($(this).css('display') != 'none') count = count + 1;
                        });
                        $(".exchange-count-list").html(count)
                socket.emit('SubAdd', {subs: currentSubs});
            }

            if ($("#tradeDataTableList").length > 0) {
                currentSubs = data[base_currency]['TRADES'];
                socket.emit('SubAdd', {subs: currentSubs});
            }
            hideLoader();
        });
    }


    socket.on("m", function(message) {
		var messageType = message.substring(0, message.indexOf("~"));
		var res = {};
		///console.log(messageType);

		if (messageType == CCC.STATIC.TYPE.CURRENTAGG) {
			data = CCC.CURRENT.unpack(message);
            var from = data['FROMSYMBOL'];
            var to = data['TOSYMBOL'];
            var fsym = CCC.STATIC.CURRENCY.getSymbol(from);
            var tsym = CCC.STATIC.CURRENCY.getSymbol(to);
            var pair = from + to;
            var c = $("."+from + '-detail-container');
            c.each(function() {
                var c = $(this);
               if (c.length > 0) {
                    var price = data['PRICE'];
                    var flashType = '';
                    if (price) {
                        var currentPrice = c.data('price');
                        portfolio_regulate_price(from,c,price);
                        c.data('price', price)
                        if (price > currentPrice) {
                            flashType = 'increment';
                        } else if(price < currentPrice) {
                            flashType = 'decrement';
                        }
                        if(isFiatCurrencyCoin) {
                            c.find('.price').html(accounting.formatNumber(price, decimalPoint,thousandSep,decimalSep));
                        } else {
                            c.find('.price').html(accounting.formatNumber(price*base_currency_rate, decimalPoint,thousandSep,decimalSep));
                        }

                        var changePTC = ((data['PRICE'] - data['OPEN24HOUR']) / data['OPEN24HOUR'] * 100).toFixed(2);
                        if (changePTC != 'NaN') {
                            var changeC = c.find('.change');
                            changeC.removeClass('change-up');
                            changeC.removeClass('change-down');
                            changeC.find('i').removeClass('icon-arrow-up');
                            changeC.find('i').removeClass('icon-arrow-down');

                            if (changePTC > 0) {
                                changePTC = "<i class='icons icon-arrow-up-circle'></i> "+ changePTC;
                                changeC.addClass('change-up');
                                changeC.find('i').addClass('icon-arrow-up');
                            } else {
                                changePTC = "<i class='icons icon-arrow-down-circle'></i> "+ changePTC;
                                changeC.addClass('change-down');
                                changeC.find('i').addClass('icon-arrow-down');
                            }
                            changeC.html(changePTC+'%');
                        }


                        if (c.data('no-flash') == undefined) {
                            c.removeClass('flash-increment');
                            c.removeClass('flash-decrement');
                            c.addClass('flash-'+flashType);
                            setTimeout(function() {
                                c.removeClass('flash-'+flashType);
                            }, 300);
                        } else {
                            c.find('.price').parent().removeClass('color-increment');
                            c.find('.price').parent().removeClass('color-decrement');
                            c.find('.price').parent().addClass('color-'+flashType);
                            setTimeout(function() {
                                 c.find('.price').parent().removeClass('color-'+flashType);
                             }, 800);
                        }

                    }


                }
            });

	    } else if(messageType == CCC.STATIC.TYPE.TRADE){
            var incomingTrade = CCC.TRADE.unpack(message);
            var maxTableSize = 30;
            console.log(incomingTrade);
            var newTrade = {
                Market: incomingTrade['M'],
                Type: incomingTrade['T'],
                ID: incomingTrade['ID'],
                Price: CCC.convertValueToDisplay(base_currency_symbol, incomingTrade['P']),
                Quantity: CCC.convertValueToDisplay('', incomingTrade['Q']),
                Total: CCC.convertValueToDisplay(base_currency_symbol, incomingTrade['TOTAL'])
            };

            if (incomingTrade['F'] & 1) {
                newTrade['Type'] = "<button class='btn btn-sm btn-success'  style='text-transform:uppercase'>"+window.lang.buy+"</button>";
            }
            else if (incomingTrade['F'] & 2) {
                newTrade['Type'] = "<button class='btn btn-sm btn-danger'  style='text-transform:uppercase'>"+window.lang.sell+"</button>";;
            }
            else {
                newTrade['Type'] = "<button class='btn btn-sm btn-secondary' style='text-transform:uppercase'>"+window.lang.unknown+"</button>";;
            }

            //display it
            var length = $('#tradeDataTableList tr').length;
            $('#tradeDataTableList #trades').after(
                "<tr class=" +  newTrade.Type + "><td><strong>" +  newTrade.Market + "</strong></td><td>" +  newTrade.Type + "</td><td>" +  newTrade.Price + "</td><td>" +  newTrade.Quantity + "</td><td>" +  newTrade.Total + "</td></tr>"
            );

            if (length >= maxTableSize) {
                $('#tradeDataTableList tr:last').remove();
            }
		} else if(messageType == CCC.STATIC.TYPE.CURRENT) {
            data = CCC.CURRENT.unpack(message);
            var market = data['MARKET'];
            var to = data['TOSYMBOL'];
            var c = $("#"+market + '-detail-container');
                   var price = data['PRICE'];
                    var flashType = '';
                    if (data['VOLUME24HOURTO'] && price) {
                        c.show();
                        var currentPrice = c.data('price');
                        c.data('price', price)
                        if (price > currentPrice) {
                            flashType = 'increment';
                        } else if(price < currentPrice) {
                            flashType = 'decrement';
                        }
                        if(isFiatCurrencyCoin) {
    //c.find('.price-order').html(price*base_currency_rate);
                            c.find('.exchange-price').html(accounting.formatNumber(price*base_currency_rate, decimalPoint,thousandSep,decimalSep));
                            //c.find('.open').html(data['OPEN24HOUR']);
                            //c.find('.open').html(accounting.formatNumber(data['OPEN24HOUR']*base_currency_rate, decimalPoint));
                            if (data['OPEN24HOUR']) c.find('.exchange-open').html(accounting.formatNumber(data['OPEN24HOUR']*base_currency_rate, decimalPoint,thousandSep,decimalSep));
                            if (data['LOW24HOUR']) c.find('.exchange-low').html(accounting.formatNumber(data['LOW24HOUR']*base_currency_rate, decimalPoint,thousandSep,decimalSep));
                            if (data['HIGH24HOUR']) c.find('.exchange-high').html(accounting.formatNumber(data['HIGH24HOUR']*base_currency_rate, decimalPoint,thousandSep,decimalSep));
                            c.find('.exchange-volume').html(accounting.formatNumber(data['VOLUME24HOURTO']*base_currency_rate, decimalPoint,thousandSep,decimalSep));
                        } else {
    //c.find('.price-order').html(price*base_currency_rate);
                            c.find('.exchange-price').html(accounting.formatNumber(price, decimalPoint,thousandSep,decimalSep));
                            //c.find('.open').html(data['OPEN24HOUR']);
                            //c.find('.open').html(accounting.formatNumber(data['OPEN24HOUR']*base_currency_rate, decimalPoint));
                            if (data['OPEN24HOUR']) c.find('.exchange-open').html(accounting.formatNumber(data['OPEN24HOUR'], decimalPoint,thousandSep,decimalSep));
                            if (data['LOW24HOUR']) c.find('.exchange-low').html(accounting.formatNumber(data['LOW24HOUR'], decimalPoint,thousandSep,decimalSep));
                            if (data['HIGH24HOUR']) c.find('.exchange-high').html(accounting.formatNumber(data['HIGH24HOUR'], decimalPoint,thousandSep,decimalSep));
                            c.find('.exchange-volume').html(accounting.formatNumber(data['VOLUME24HOURTO'], decimalPoint,thousandSep,decimalSep));
                        }
                        var changePTC = ((data['PRICE'] - data['OPEN24HOUR']) / data['OPEN24HOUR'] * 100).toFixed(2);
                        if (changePTC != 'NaN') {
                            var changeC = c.find('.exchange-change');
                            changeC.removeClass('change-up');
                            changeC.removeClass('change-down');
                            changeC.find('i').removeClass('icon-arrow-up');
                            changeC.find('i').removeClass('icon-arrow-down');

                            if (changePTC > 0) {
                                changePTC = "<i class='icons icon-arrow-up-circle'></i> "+ changePTC;
                                changeC.addClass('change-up');
                                changeC.find('i').addClass('icon-arrow-up');
                            } else {
                                changePTC = "<i class='icons icon-arrow-down-circle'></i> "+ changePTC;
                                changeC.addClass('change-down');
                                changeC.find('i').addClass('icon-arrow-down');
                            }
                            changeC.html(changePTC+'%');
                        }

                        if (c.data('no-flash') == undefined) {
                            c.removeClass('flash-increment');
                            c.removeClass('flash-decrement');
                            c.addClass('flash-'+flashType);
                            setTimeout(function() {
                                c.removeClass('flash-'+flashType);
                            }, 500);
                        }

                    } else {
                        c.hide();
                    }
                    var count = 0;
                    $('.coin-exchange-container').each(function() {
                        if($(this).css('display') != 'none') count = count + 1;
                    });
                    $(".exchange-count-list").html(count)
            console.log(data);
		}
	});
}
function get_coin_on_page() {
    var result = [];
    $('.coin-detail-container').each(function() {
        result.push('5~CCCAGG~'+$(this).data('symbol')+'~'+base_currency+'')
    });
    return result;
}

function get_exchange_on_page() {
    var result = [];
    $('.coin-exchange-container').each(function() {
        result.push('0~'+$(this).data('name')+'~'+$(this).data('symbol')+'~'+base_currency+'')
    });
    return result;
}

function portfolio_regulate_price(from,c,price) {

    value = c.find('.coin-quantity').data('quantity') * price * base_currency_rate;
    c.find('.value').html(accounting.formatNumber(value, decimalPoint,thousandSep,decimalSep));
    c.find('.value').data('value', value);
    invest = c.find('.invest').data('value');
    profit = value - invest;
    c.find('.profit').html(accounting.formatNumber(profit, decimalPoint,thousandSep,decimalSep));
    c.find('.profit').data('value', profit);
    re = (profit / invest) * 100;
    c.find('return').html(re);

    //do overall calculation
    var overallValue = 0;
    var overallProfit = 0;
    $('.coin-detail-container').each(function() {
        overallValue = overallValue + $(this).find('.value').data('value');
        overallProfit = overallProfit + $(this).find('.profit').data('value');
    });
    $('.net-worth').html(accounting.formatNumber(overallValue, decimalPoint,thousandSep,decimalSep));
    $('.total-profit').html(accounting.formatNumber(overallProfit, decimalPoint,thousandSep,decimalSep));
    var totalInvest = $(".total-invest").data('value');
    var re = (overallProfit / totalInvest) * 100
    $('.total-return').html(Math.round(re));
}

function do_calculator() {
    var calc = $('.currency-converter');
    var type  = $("#calculator-switch").data('type');
    var inp = calc.find('.converter-from-'+type);
    var from = calc.find('.from');
    var to = calc.find('.to');

    var answerFrom = calc.find('.answer-right');
    var answerTo = calc.find('.answer-left');
    if (inp.val() == '') return false;
    if (type == 'left') {
        //answerFrom.html(inp.val() + ' '+from.val());
    } else {
        //answerTo.html(inp.val() + ' '+to.val());
    }
    $.ajax({
        url :baseUrl + 'convert',
        data :{input:inp.val(),from:from.val(),to:to.val(),type:type},
        success : function(c) {
            if (type == 'left') {
                answerTo.html(c);
            } else{
                answerFrom.html(c);
            }
        }
    })
}
function change_calculator(t) {
    var type = $(t).data('type');
    $('.converter-from-right').hide();
    $('.converter-from-left').hide();
    $('.answer-right').hide();
    $('.answer-left').hide();
    type = (type == 'left') ? 'right' : 'left';
    $('.converter-from-' + type).fadeIn();
    $('.answer-' + type).fadeIn();

    if (type == 'left') {
        $(t).find('i').removeClass('icon-arrow-up-circle');
        $(t).find('i').addClass('icon-arrow-down-circle');
        $(t).data('type', 'left');
        $('.converter-from-left').focus();
    } else {
        $(t).find('i').addClass('icon-arrow-up-circle');
        $(t).find('i').removeClass('icon-arrow-down-circle');
        $('.converter-from-right').focus();
        $(t).data('type', 'right');
    }
    return false;
}
function reload_init(d) {
    $('.tool-tip').tooltip();
    if (d == undefined) {
    $('.edit-caption-modal').on('shown.bs.modal', function (e) {
        reload_init(true);
    })
    }
    $('.sparkline-charts').each(function() {
        //var values = $(this).data('value').split(',')
        $(this).sparkline('html', {width:$(this).data('width'), lineWidth: 1,spotColor:'#3A7ABA',maxSpotColor:'#3A7ABA',lineColor:'#0275D8',fillColor:'rgba(255,255,255,0)'})
    });


    if($('#site-overview-chart').length  > 0) {
        var ctx = document.getElementById('site-overview-doughnut').getContext("2d");;
        var config = get_site_chart_config();
        new Chart(ctx, config);
    }

   if($('#portfolio-overview-chart').length  > 0) {
        var ctx = document.getElementById('portfolio-overview-doughnut').getContext("2d");;
        var config = get_portfolio_chart_config();
        new Chart(ctx, config);
    }

   if($('#portfolio-worth-chart').length  > 0) {
        var ctx = document.getElementById('portfolio-worth-doughnut').getContext("2d");;
        var config = get_portfolio_worth_chart_config();
        new Chart(ctx, config);
    }



   if($('#portfolio-profit-chart').length  > 0) {
        var ctx = document.getElementById('portfolio-profit-doughnut').getContext("2d");;
        var config = get_portfolio_profit_chart_config();
        new Chart(ctx, config);
    }

    const observer = lozad('.lozad', {
        rootMargin: '10px 0px', // syntax similar to that of CSS Margin
        threshold: 0.1 // ratio of element convergence
    });
    observer.observe();

    subscribe_for_updates();

    $('.gif').gifplayer();

   $('.sidebar__inner-ld').sticky({
               topSpacing: 60,
               bottomSpacing: 100,
               center : true
             });

    $('.emoji-input-top').each(function() {
            var target = $(this);
            $(this).emojioneArea({
                    pickerPosition :'top',
                            shortnames : true,
                            saveEmojisAs : 'unicode',
                                                                         //standalone : true,
                    events: {
                            keyup: function (editor, e) {
                                target.val(this.getText())
                                //e.stopPropagation();
                                //alert(this.getText());
                            },
                            click : function(editor,event) {
                                editor.focus();
                            }
                    }
            });

        });


     if ($("#editor").length > 0) {
        CKEDITOR.replace( 'editor' );
     }

     $(".rich-editor").each(function() {
        CKEDITOR.replace( $(this).attr('id') );
     })

    $('.emoji-inputss').each(function() {
        var target = $(this);
        var parent = target.parent();
        parent.find('.emojionearea').remove();
        $(this).emojioneArea({
                    pickerPosition :'bottom',
                            shortnames : true,
                            saveEmojisAs : 'unicode',
                                                                         //standalone : true,
                    events: {
                            keyup: function (editor, e) {
                                target.val(this.getText())
                                //e.stopPropagation();
                                //alert(this.getText());
                            },
                            click : function(editor,event) {
                                editor.focus();
                            }
                    }
        });



    });



    $('.m-popover').popover({
        html : true,

    })
    var jcarousel = $('.jcarousel');


        jcarousel.on('jcarousel:reload jcarousel:create', function () {
            var carousel = $(this),
                width = carousel.innerWidth();

            if (width >= 600) {
                width = width / 3;
            } else if (width >= 350) {
                width = width / 2;
            }

            carousel.jcarousel('items').css('width', Math.ceil(width) + 'px');
        })
        .jcarousel({
            wrap: null
        });

    $('.jcarousel-control-prev')
        .jcarouselControl({
            target: '-=1'
        });

    $('.jcarousel-control-next')
        .jcarouselControl({
            target: '+=1'
        });

    $('.jcarousel-pagination')
        .on('jcarouselpagination:active', 'a', function() {
            $(this).addClass('active');
        })
        .on('jcarouselpagination:inactive', 'a', function() {
            $(this).removeClass('active');
        })
        .on('click', function(e) {
            e.preventDefault();
        })
        .jcarouselPagination({
            perPage: 1,
            item: function(page) {
                return '<a href="#' + page + '">' + page + '</a>';
            }
        });

    $('#dataTable').DataTable();
    $('#exchangeDataTable').DataTable({
         paging: false,
         searching: false,
         ordering : true,
         order : [[1, 'asc']]
    });
    if(d == undefined && $("#coin-chart").length > 0) {
       init_coin_chart();
    }

    if(d == undefined && $("#home-coin-chart").length > 0) {
        var first = $('.each-top-coin');
        init_coin_chart(first.data('symbol'),first.data('currency'),first.data('logo'));
    }

    $( ".columns-list" ).sortable({
        items : '.each-column'
    });

     $('.toggle-one').bootstrapToggle();

    $('.datepicker').each(function() {
        var timepicker = false;

        var format = dateFormat;
        if ($(this).data('time') != undefined) {
            timepicker = true;
            format += ' H:i';
        }

        $(this).datetimepicker({
                timepicker :timepicker,
                format:format
            });
    });

    $('.color-picker').each(function() {
                var c = $(this);
                var input = c.find('input');
                var holder = c.find('.holder')
                input.ColorPicker({
                    onSubmit: function(hsb, hex, rgb, el) {
                    		$(el).val('#'+hex);
                    		$(el).ColorPickerHide();
                    		holder.css('background-color', '#'+hex);
                    	},
                    onBeforeShow: function () {
                    	$(this).ColorPickerSetColor(this.value);
                    },
                    onChange: function (hsb, hex, rgb) {
                    		$(el).val('#'+hex);
                    		holder.css('background-color', '#'+hex);
                    }
                }).bind('keyup', function(){
                  	$(this).ColorPickerSetColor(this.value);
                  });
        })

        $(".select2").select2();
        $(".select2-container").each(function() {
                var n = $(this).next();
                if (n.hasClass('select2-container')) n.remove();
            });

        $('textarea').each(function () {
            $(this).on('keyup', RTLText.onTextChange);
            $(this).on('keydown', RTLText.onTextChange);
        })
    runReloadHooks();
}


function check_events() {
return false;
    var userid = '';
    var lastcheck = '';
    if (isloggedin == 0) return false;
    $.ajax({
        url : baseUrl + 'check/events',
        data : {userid:userid,lastcheck:lastcheck},
        success : function(r) {
            if (pre_process_result(r)) return false;
             var json = jQuery.parseJSON(r);
             if (json.notify != 0) {
                var a = $('.header-notification-icon');
                a.find('.notify').remove();
                a.append('<span class="notify"></span>');
             } else {
                 var a = $('.header-notification-icon');
                 a.find('.notify').remove();
             }
        }
    })
}



function do_search(t) {
    var i = $(t);

    if (i.val().length > 0) {
    showLoader();
        $.ajax({
            url :baseUrl + 'search',
            data : {term:i.val()},
            success : function(r) {
                hideLoader();
                $("#search-viewer .content").html(r);
                reload_init(true);
            }
        })
    }
}



function scroll_bottom(div) {
    var scroller = $(div);
    var height = scroller[0].scrollHeight - $(scroller).height();
    height += 3000;
    $(div).animate({ scrollTop: height}, "slow");
}

function read_more(t, id) {
    var o = $(t);
    var container = $('#' + id);
    container.find('span').hide();
    container.find('.text-full').fadeIn();
    if (container.find('.text-full').find('span').length > 0) {
        container.find('.text-full').find('span').fadeIn();
    }
    o.hide();
    return false;
}

function open_search() {
    $("#search-viewer").fadeIn();
    $("#search-viewer .head input").focus();
    return false;
}

function hide_search() {
    $("#search-viewer").fadeOut();
    return false;
}

function block_user(id) {
    my_confirm(function() {
        showLoader();
        $.ajax({
            url : baseUrl + 'user/block?id=' +id,
            success : function(r) {
                if (pre_process_result(r)) return false;
                notify(r, 'success');
                load_page(baseUrl);
                hideLoader();
            }
        })
    });
    return false;
}
function unblock_user(id) {
    my_confirm(function() {
        showLoader();
        $.ajax({
            url : baseUrl + 'user/unblock?id=' +id,
            success : function(r) {
                if (pre_process_result(r)) return false;
                notify(r, 'success');
                load_page(location.href);
            }
        })
    });
    return false;
}

function do_report(type,id) {
    var modal = $("#reportModal");
    modal.modal("show");
    modal.find('#send-report-btn').click(function() {
            $(".loader-container").fadeIn();
            $.ajax({
                url : baseUrl + 'report/content',
                data : {type:type,id:id,text:modal.find('textarea').val()},
                success : function(r) {
                    if (pre_process_result(r)) return false;
                    modal.modal("hide");
                    $(".loader-container").fadeOut();
                    notify(r, 'success');
                    text:modal.find('textarea').val('')
                }
            })
    });

    return false;
}
function load_page(link) {
    showLoader();
    $('body').click();

    window.onpopstate = function(e) {
            load_page(window.location, true);
        }
        $.ajax({
            url : link,
            success : function(data) {
            hideLoader();
                var data = jQuery.parseJSON(data);
                document.title = data.title;
                $("#page-content").html(data.content);
                window.history.pushState({},'New URL:' + link, link);
                $(window).scrollTop(0);
                $("#header-nav li").removeClass('active');
                if (data.active_menu != '' ) {
                    $("#"+data.active_menu + "-nav").addClass('active');
                }

            },
            complete : function() {
                reload_init();
                $(".notification-dropdown").fadeOut();
                hide_search();
                           }
        });
}

$(function() {
    reload_init();

    check_events();
    $(document).on("click", ".ajax-link", function() {
            var link = $(this).attr('href');
            load_page(link);
            return false;
        });
    $(document).on("submit", "#signup-form", function() {
        $(".loader-container").fadeIn();
        $(this).ajaxSubmit({
            url : baseUrl + 'signup',
            success : function(result) {
                var json = jQuery.parseJSON(result);
                if (json.status == 1) {
                    //we can redirect to the next destination
                    window.location.href = json.url;
                    notify(json.message, 'success');
                } else {
                    notify(json.message, 'error');
                    $(".loader-container").fadeOut();
                }
            }
        })
        return false;
    });

     $(document).on("submit", "#login-form", function() {
            $(".loader-container").fadeIn();
            $(this).ajaxSubmit({
                url : baseUrl + 'login',
                success : function(result) {
                    var json = jQuery.parseJSON(result);
                    if (json.status == 1) {
                        //we can redirect to the next destination
                        window.location.href = json.url;
                        notify(json.message, 'success');
                    } else {
                        notify(json.message, 'error');
                        $(".loader-container").fadeOut();
                    }
                }
            })
            return false;
        });


     $(document).on('submit', '#post-editor-form', function() {
            if (pre_process_result()) return false;
            var image = $('.post-photo-input').val();
            var noContent = true;
            if (image != ''){
                noContent = false;
            }
            if ($(".emoji-input").data("emojioneArea") == undefined) {
                if ($(".emoji-input").val() != '') noContent = false;
            } else {
                if ($(".emoji-input").data("emojioneArea").getText() != '') noContent = false;
            }
            if (noContent) {
                 notify(window.lang.post_empty_error, 'error')
                 return false;
            }
            showLoader();
            $(".loader-container").fadeIn();
            $(this).ajaxSubmit({
                url : baseUrl + 'post/add',
                success : function(r) {
                    if (pre_process_result(r)) return false;
                    hideLoader();
                    $(".loader-container").fadeOut();
                    var result = jQuery.parseJSON(r);
                    if (result.status == 1) {
                        notify(result.message, 'success');
                        if ($("#posts-list").length > 0) {
                            $("#empty-post-content").remove();
                            $("#posts-list").prepend(result.post);
                        }
                        if ($("#threadModal").length > 0) {
                            var coin = $("#post-modal-coin").val();
                            load_page(baseUrl+'coin/' +coin+'/forum');
                            $("#threadModal").modal('hide');
                        }
                        reset_post_editor();
                        reload_init();
                    } else {
                        notify(result.message, 'error');
                    }

                }
            })
            return false;
        });

    $(document).on('click', '.like-btn', function() {
        if (pre_process_result()) return false;
        var l = $(this);
           if (l.hasClass('liked')) {
              l.removeClass('liked');
           } else {
                l.addClass('liked')
           }
           showLoader();
           $.ajax({
                url : baseUrl + 'like/process',
                data : {id:l.data('id')},
                success : function(r) {
                    if (pre_process_result(r)) return false;
                    hideLoader();
                    json = jQuery.parseJSON(r);
                    $('.likes-count-' + l.data('id')).html(json.count);
                }
           });
           return false;
    });


    $(document).on('click', '.post-comment-btn', function() {
        var l = $(this);
        $(".comment-form-" + l.data('id')+ " input").focus();
        return false;
    });

    $(document).on('submit', '.comment-form form', function() {
        if (pre_process_result()) return false;
        var form = $(this);
        id = form.data('id');
        type = form.data('type');
        if ((form.find('input').length > 0 && form.find('input').val() == '') || (form.find('textarea').length > 0 && form.find('textarea').val() == '')) return false;
        showLoader();
        form.ajaxSubmit({
            url : baseUrl + 'comment/add',
            data : {id:id,type:type},
            success : function(c) {
                if (pre_process_result(c)) return false;
                hideLoader();
                $('.comments-lists-' + id).append(c);
                form.find('input').val('')
                form.find('textarea').val('')
            }
        })
        return false;
    });

    $(document).on('click', '.comment-remove-btn', function() {
        var id = $(this).data('id');
         my_confirm(function() {
            $('.comment-' + id).remove();
            $.ajax({
                url : baseUrl + 'comment/remove',
                data :{id:id},
                success : function(r) {
                    if (pre_process_result(r)) return false;
                }
            })
         });
         return false;
    });

    $(document).on('click', '.load-more-comment', function() {
        showLoader();
        l = $(this);
        id = $(this).data('id');
        type = $(this).data('type');
        offset = $(this).data('offset');
        container = $(".comments-lists-" + id);
        $.ajax({
            url : baseUrl + 'comment/more',
            data : {id:id,offset:offset,type:type},
            success: function(r) {
                r = jQuery.parseJSON(r);
                l.data('offset', r.offset);
                container.prepend(r.content);
                hideLoader();
                if (r.content == '')l.fadeOut();
            }
        })
        return false;
    });
    $(document).on('click', '.follow-btn', function() {
        showLoader();
        id = $(this).data('id');
        $.ajax({
            url : baseUrl + 'follow/process?type=follow',
            data : {id:$(this).data('id'),entity_type:$(this).data('type')},
            success : function(r){
                if (pre_process_result(r)) return false;
                notify(r, 'success')
                hideLoader();
                $(".follow-btn-"+id).hide();
                $(".unfollow-btn-"+id).fadeIn();
            }
        })
        return false;
    });

    $(document).on('click', '.unfollow-btn', function() {
        showLoader();
        id = $(this).data('id');
        $.ajax({
            url : baseUrl + 'follow/process?type=unfollow',
            data : {id:$(this).data('id'),entity_type:$(this).data('type')},
            success : function(r){
                if (pre_process_result(r)) return false;
                notify(r, 'success')
                hideLoader();
                $(".follow-btn-"+id).fadeIn();
                $(".unfollow-btn-"+id).hide();
            }
        })
        return false;
    });

    $(document).on('submit', '.edit-caption-form', function(){
        form  = $(this);
        id = form.data('id');
        showLoader();

        form.ajaxSubmit({
            url : baseUrl + 'post/save',
            data : {id:id},
            success : function(r) {
                if (pre_process_result(r)) return false;
                notify(r, 'success');
                hideLoader();
                $('.caption-'+id).html(form.find('textarea').val());
                $('.caption-'+id).parent().fadeIn();
                $("#caption-edit-modal-" + id).modal('hide')
            }
        })
        return false;
    });

    window.postPaginating = false;
        $(window).scroll(function() {
           if($(window).scrollTop() + $(window).height() > $(document).height() - 100) {
               if($('.post-list').length > 0 && !window.postPaginating) {
                    var l = $('.post-list');
                    window.postPaginating = true;
                    showLoader();
                    userid = l.data('userid');
                    page = l.data('page');
                    type = l.data('types');
                    offset = l.data('offset');
                    $.ajax({
                        url : baseUrl + 'post/load/more',
                        data :{userid:userid,page:page,type:type,offset:offset},
                        success : function(r){
                            if (pre_process_result(r)) return false;
                            hideLoader();
                            window.postPaginating = false;
                            json = jQuery.parseJSON(r);

                            $('.post-list').append(json.content);
                            $('.post-list').data('offset', json.offset);
                            reload_init();
                        }
                    })
               }
           }
        });
    setInterval(function() {
        check_events();

    }, checkTimeInterval);

    $(document).on('click', '.each-top-coin', function() {
        var first = $(this);
        init_coin_chart(first.data('symbol'),first.data('currency'),first.data('logo'));
        return false;
    });

    $(document).on('submit', '#columns-form', function() {
        showLoader();
        $(this).ajaxSubmit({
            url : baseUrl + 'save/columns',
            success: function() {
                hideLoader();
                $("#columnModal").modal('toggle');
                load_page(window.location.href);
            }
        })
        return false;
    });

    $(document).on('submit', '#portfolio-form', function() {
        $(".loader-container").fadeIn();
        showLoader();
        $(this).ajaxSubmit({
            url : baseUrl + 'portfolio/save',
            success : function(r) {
                hideLoader();
                $("#portfolioModal").modal('toggle');
                $(".loader-container").fadeOut();
                load_page(r);
            }
        })

        return false;
    });

    $(document).on('submit', '#edit-portfolio-form', function() {
        $(".loader-container").fadeIn();
        showLoader();
        $(this).ajaxSubmit({
            url : baseUrl + 'portfolio/save',
            success : function(r) {
                hideLoader();
                $("#portfolioEditModal").modal('toggle');
                $(".loader-container").fadeOut();
                load_page(r);
            }
        })

        return false;
    });

    $(document).on('submit', '#portfolio-coin-form', function() {
       $(".loader-container").fadeIn();
        showLoader();
        $(this).ajaxSubmit({
            url : baseUrl + 'portfolio/coin/save',
            success : function(r) {
                hideLoader();
                $("#addCoinModal").modal('toggle');
                $(".loader-container").fadeOut();
                load_page(r);
            }
        })

        return false;
    });

    $(document).on('click', '.confirm-link', function() {
        var link = $(this).attr('href');
        my_confirm(function() {
            window.location.href = link;
        })
        return false;
    });
})