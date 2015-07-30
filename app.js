/**
 * Created by Marc on 29.04.2015.
 */

(function(){
    var app = angular.module('app', []);

    app.controller('controller', function($scope, $http, $location){
        $scope.page = 1;

        api('action=tables', function(tables){
            $scope.tables = tables;
        });

        $scope.changeTable = function(table){
            $scope.table = table;
            $scope.page = 1;

            api('action=columns&table=' + table, function(data){
                $scope.primary = data.primary;
                $scope.columns = [];
                $scope.columnTypes = data.columns;

                for(var i = 0; i < data.columns.length; i++){
                    $scope.columns.push(data.columns[i].name);
                }

                $location.search('table', table);
            });

            api('action=foreign-keys&table=' + table, function(data){
                $scope.foreignKeys = data;
            });

            loadRows();
        };

        $scope.previous = function(){
            if($scope.page > 1){
                $scope.page--;
                $scope.selected = null;

                loadRows();
            }
        };

        $scope.next = function(){
            if($scope.rows.length > 0){
                $scope.page++;
                $scope.selected = null;

                loadRows();
            }
        };

        $scope.select = function(row){
            if(row !== $scope.selected){
                $scope.selected = row;

                setTimeout(function(){
                    initDateTimePicker(row);
                }, 1);
            }
        };

        $scope.isSelected = function(row){
            return row === $scope.selected;
        };

        $scope.save = function(row){
            api('action=save&table=' + $scope.table + '&primary=' + $scope.primary + '&row=' + JSON.stringify(row, null , 0).replace(/&/g, '%26'), function(data){
                if(data.error){
                    $scope.error = data.error;

                    return;
                }

                $scope.error = null;
                $scope.selected = null;
            });
        };

        $scope.addRow = function(){
            var newRow = {};
            var highestId = 0;

            for(var i = 0; i < $scope.rows.length; i++){
                var row = $scope.rows[i];
                var id = row[$scope.primary];

                if(id > highestId){
                    highestId = id;
                }
            }

            newRow[$scope.primary] = highestId + 1;

            $scope.rows.unshift(newRow);
            $scope.select(newRow);
        };

        $scope.delete = function(row){
             api('action=delete&table=' + $scope.table + '&primary=' + $scope.primary + '&value=' + row[$scope.primary], function(){
                var pos = $scope.rows.indexOf(row);

                $scope.selected = null;

                $scope.rows.splice(pos, 1);
             });
        };

        $scope.isForeignKey = function(column){
            return $scope.foreignKeys && $scope.foreignKeys[column];
        };

        $scope.getForeignKeyName = function(row, column){
            var items = $scope.foreignKeys[column];

            for(var i = 0; i < items.length; i++){
                var item = items[i];

                if(item.id === row[column]){
                    return item.name;
                }
            }

            return row[column];
        };

        $scope.getColumnType = function(column){
            for(var i = 0; i < $scope.columnTypes.length; i++){
                var columnType = $scope.columnTypes[i]

                if(columnType.name === column){
                    return columnType.type;
                }
            }

            return false;
        };

        initTable();

        function api(query, cb){
            $http.get('api.php?' + query).success(cb);
        }

        function loadRows(){
            api('action=rows&table=' + $scope.table + '&page=' + $scope.page, function(rows){
                $scope.rows = rows;

                for(var i = 0; i < rows.length; i++){
                    var row = rows[i];

                    for(var column in row){
                        if(row.hasOwnProperty(column)){
                            var num = filterInt(row[column]);

                            if(!isNaN(num)){
                                row[column] = num;
                            }
                        }
                    }
                }
            });

            function filterInt(value){
                if(/^(\-|\+)?([0-9]+|Infinity)$/.test(value)){
                    return Number(value);
                }

                return NaN;
            }
        }

        function initTable(){
            var search = $location.search();
            var table = search.table;

            if(table){
                $scope.changeTable(table);
            }
        }

        function initDateTimePicker(row){
            var $elements = $('[data-datetimepicker]')

            $elements.each(function(index, element){
                var format = $(element).data('format');
                var column = $(element).data('column');

                if(format === 'datetime'){
                    format = 'YYYY-MM-DD HH:mm:ss';
                }else if(format === 'date'){
                    format = 'YYYY-MM-DD';
                }

                $(element).datetimepicker({
                    locale: 'de',
                    format: format
                }).on('dp.change', function(){
                    row[column] = $(element).val();

                    $scope.$apply();
                });
            });
        }
    });
}());