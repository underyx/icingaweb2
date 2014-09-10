// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

(function(Icinga, $) {

    "use strict";

    Icinga.Behaviors = Icinga.Behaviors || {};

    var Sparkline = function (icinga) {
        this.icinga = icinga;
    };

    Sparkline.prototype.apply = function(el) {
        var self = this, icinga = this.icinga;

        $('span.sparkline', el).each(function(i, element) {
            // read custom options
            var $spark            = $(element);
            var labels            = $spark.attr('labels').split('|');
            var formatted         = $spark.attr('formatted').split('|');
            var tooltipChartTitle = $spark.attr('sparkSparklineChartTitle') || '';
            var format            = $spark.attr('tooltipformat');
            var hideEmpty         = $spark.attr('hideEmptyLabel') === 'true';
            $spark.sparkline(
                'html',
                {
                    enableTagOptions: true,
                    tooltipFormatter: function (sparkline, options, fields) {
                        var out       = format;
                        if (hideEmpty && fields.offset === 3) {
                            return '';
                        }
                        var replace   = {
                            title:     tooltipChartTitle,
                            label:     labels[fields.offset] ? labels[fields.offset] : fields.offset,
                            formatted: formatted[fields.offset] ? formatted[fields.offset] : '',
                            value:     fields.value,
                            percent:   Math.round(fields.percent * 100) / 100
                        };
                        $.each(replace, function(key, value) {
                            out = out.replace('{{' + key + '}}', value);
                        });
                        return out;
                    }
            });
        });
    };

    Sparkline.prototype.bind = function() {
    };

    Sparkline.prototype.unbind = function() {
    };

    Icinga.Behaviors.Sparkline = Sparkline;

}) (Icinga, jQuery);
