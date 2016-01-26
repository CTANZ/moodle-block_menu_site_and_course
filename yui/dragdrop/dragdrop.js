YUI.add('moodle-block_menu_site_and_course-dragdrop', function(Y) {

    var CSS = {
        ACTIVITY : 'activity',
        COMMANDSPAN : 'span.commands',
        CONTENT : 'content',
        COURSECONTENT : 'course-content',
        EDITINGMOVE : 'editing_move',
        ICONCLASS : 'iconsmall',
        JUMPMENU : 'jumpmenu',
        LEFT : 'left',
        LIGHTBOX : 'lightbox',
        MOVEDOWN : 'movedown',
        MOVEUP : 'moveup',
        PAGECONTENT : 'page-content',
        RIGHT : 'right',
        SECTION : 'section',
        SECTIONADDMENUS : 'section_add_menus',
        SECTIONHANDLE : 'section-handle',
        SUMMARY : 'summary'
    };

    var BLOCKMENUSITEANDCOURSEDRAGSECTION = function() {
        BLOCKMENUSITEANDCOURSEDRAGSECTION.superclass.constructor.apply(this, arguments);
    };
    Y.extend(BLOCKMENUSITEANDCOURSEDRAGSECTION, M.core.dragdrop, {
        sectionlistselector : 'div.block.block_menu_site_and_course ul.list.section-list',
        sectionsselector : null,

        initializer : function(params) {
            // Set group for parent class
            this.groups = ['blockmenusection'];
            this.samenodeclass = 'r0';
            this.parentnodeclass = 'section-list';

            // Check if we are in single section mode
            /*if (Y.Node.one('.'+CSS.JUMPMENU)) {
                return false;
            }*/

            // Set up selector for actual sections in course content
            this.sectionsselector = M.course.format.get_section_wrapper(Y);
            if (this.sectionsselector) {
                this.sectionsselector = '.'+CSS.COURSECONTENT+' '+this.sectionsselector;
            }

            // Initialise sections dragging
            // Make each li element in the list of sections draggable
            var list = Y.one(this.sectionlistselector);
            list.generateID();
            this.sectionlistselector = '#'+list.get('id');
            var del = new Y.DD.Delegate({
                container: this.sectionlistselector,
                nodes: 'li.r0',
                target: true,
                handles: ['.icon'],
                dragConfig: {groups: this.groups}
            });
            del.dd.plug(Y.Plugin.DDProxy, {
                // Don't move the node at the end of the drag
                moveOnEnd: false,
                cloneNode: true
            });
            del.dd.plug(Y.Plugin.DDConstrained, {
                // Keep it inside the .course-content
                constrain: 'div.block.block_menu_site_and_course #nav'
            });
            del.dd.plug(Y.Plugin.DDWinScroll);
        },

        get_section_id : function(node) {
            return Number(node.getData('sectionid'));
        },

        get_course_section_id : function(node) {
            return Number(node.get('id').replace(/section-/i, ''));
        },

        /*
         * Drag-dropping related functions
         */
        drag_start : function(e) {
            // Get our drag object
            var drag = e.target;
            drag.get('dragNode').setContent(drag.get('node').get('innerHTML'));
        },

        drag_dropmiss : function(e) {
            // Missed the target, but we assume the user intended to drop it
            // on the last last ghost node location, e.drag and e.drop should be
            // prepared by global_drag_dropmiss parent so simulate drop_hit(e).
            this.drop_hit(e);
        },

        drop_hit : function(e) {
            var drag = e.drag;
            // Get a reference to our drag node
            var dragnode = drag.get('node');
            var dropnode = e.drop.get('node');
            // Prepare some variables
            var dragnodeid = Number(this.get_section_id(dragnode));
            var dropnodeid = Number(this.get_section_id(dropnode));

            var loopstart = dragnodeid;
            var loopend = dropnodeid;

            if (this.goingup) {
                loopstart = dropnodeid;
                loopend = dragnodeid;
            }

            // Update section IDs in menu block nodes' data
            var menusectionlist = Y.one(this.sectionlistselector).all('li.r0');
            menusectionlist.each(function(menusection, i, menulist) {
                    menusection.setData('sectionid', i+1);
                }, this
            );

            // Add lightbox if it not there
            var lightbox = M.util.add_lightbox(Y, dragnode);

            var params = {};

            // Handle any variables which we must pass back through to
            var pageparams = this.get('config').pageparams;
            for (varname in pageparams) {
                params[varname] = pageparams[varname];
            }

            // Prepare request parameters
            params.sesskey = M.cfg.sesskey;
            params.courseId = this.get('courseid');
            params['class'] = 'section';
            params.field = 'move';
            params.id = dragnodeid;
            params.value = dropnodeid;

            // Do AJAX request
            var uri = M.cfg.wwwroot + this.get('ajaxurl');

            Y.io(uri, {
                method: 'POST',
                data: params,
                on: {
                    start : function(tid) {
                        lightbox.show();
                    },
                    success: function(tid, response) {
                        // Update section titles, we can't simply swap them as
                        // they might have custom title
                        try {
                            var responsetext = Y.JSON.parse(response.responseText);
                            if (responsetext.error) {
                                new M.core.ajaxException(responsetext);
                            }
                            if (!Y.Node.one('.'+CSS.JUMPMENU)) {
                                // Swap actual sections
                                var dragsection = Y.one('#section-'+dragnodeid);
                                var dropsection = Y.one('#section-'+dropnodeid);
                                if (!this.goingup) {
                                    dropsection = dropsection.next('.'+M.course.format.get_sectionwrapperclass());
                                }
                                dragsection = dragsection.ancestor().removeChild(dragsection);
                                dropsection.ancestor().insertBefore(dragsection, dropsection);

                                // Get the list of nodes
                                var sectionlist = Y.Node.all(this.sectionsselector);

                                // Classic bubble sort algorithm is applied to the section
                                // nodes between original drag node location and the new one.
                                do {
                                    var swapped = false;
                                    for (var i = loopstart; i <= loopend; i++) {
                                        if (this.get_course_section_id(sectionlist.item(i-1)) > this.get_course_section_id(sectionlist.item(i))) {
                                            // Swap section id
                                            var sectionid = sectionlist.item(i-1).get('id');
                                            sectionlist.item(i-1).set('id', sectionlist.item(i).get('id'));
                                            sectionlist.item(i).set('id', sectionid);
                                            // See what format needs to swap
                                            M.course.format.swap_sections(Y, i-1, i);
                                            // Update flag
                                            swapped = true;
                                        }
                                    }
                                    loopend = loopend - 1;
                                } while (swapped);

                                M.course.format.process_sections(Y, sectionlist, responsetext, loopstart, loopend);
                            }
                        } catch (e) {}

                        // Finally, hide the lightbox
                        window.setTimeout(function(e) {
                            lightbox.hide();
                        }, 250);
                    },
                    failure: function(tid, response) {
                        this.ajax_failure(response);
                        lightbox.hide();
                    }
                },
                context:this
            });
        }

    }, {
        NAME : 'block_menu_site_and_course-dragdrop',
        ATTRS : {
            courseid : {
                value : null
            },
            ajaxurl : {
                'value' : 0
            },
            config : {
                'value' : 0
            }
        }
    });

    M.block_menu_site_and_course = M.block_menu_site_and_course || {};
    M.block_menu_site_and_course.init_section_dragdrop = function(params) {
        new BLOCKMENUSITEANDCOURSEDRAGSECTION(params);
    }
}, '@VERSION@', {requires:['base', 'node', 'io', 'dom', 'dd', 'dd-scroll', 'moodle-core-dragdrop', 'moodle-core-notification', 'moodle-course-coursebase']});
