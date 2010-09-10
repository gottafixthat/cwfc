####### This section is automatically generated

VERSION    = 2
PATCHLEVEL = 0
SUBLEVEL   = 0
RELEASE    = 0

export VERSION
export PATCHLEVEL
export SUBLEVEL

export FULLVERSION=$(VERSION).$(PATCHLEVEL).$(SUBLEVEL)

rpms:
	-cat rpm/cwfc.spec | sed -e s/{VERSION}/$(FULLVERSION)/ -e s/{RELEASE}/$(RELEASE)/ > rpm/cwfc-$(FULLVERSION).spec
	-rm -rf /var/tmp/cwfc
	-mkdir /var/tmp/cwfc
	-mkdir /var/tmp/cwfc/BUILD
	-mkdir /var/tmp/cwfc/RPMS
	-mkdir /var/tmp/cwfc/RPMS/i386
	-mkdir /var/tmp/cwfc/SRPMS
	-mkdir /var/tmp/cwfc/SPECS
	-mkdir /var/tmp/cwfc/SOURCES
	-mkdir /var/tmp/cwfc/cwfc-$(FULLVERSION)
	-cp rpm/rpmrc /var/tmp/cwfc/
	-tar --exclude CVS --exclude *~ --exclude *.rpm --exclude .svn -zcf /var/tmp/cwfc/SOURCES/cwfc-$(FULLVERSION).tgz Makefile *php doc smarty
	-tar -C /var/tmp/cwfc/cwfc-$(FULLVERSION) -zxf /var/tmp/cwfc/SOURCES/cwfc-$(FULLVERSION).tgz
	-tar -C /var/tmp/cwfc -zcf /var/tmp/cwfc/SOURCES/cwfc-$(FULLVERSION).tgz cwfc-$(FULLVERSION)
	-cat rpm/cwfc.spec | sed -e s/{VERSION}/$(FULLVERSION)/ -e s/{RELEASE}/$(RELEASE)/ > /var/tmp/cwfc/SPECS/cwfc-$(FULLVERSION).spec
	-cp rpm/cwfc-$(FULLVERSION).spec /var/tmp/cwfc/SPECS/
	-rpmbuild -ba /var/tmp/cwfc/SPECS/cwfc-$(FULLVERSION).spec
	-cp /var/tmp/cwfc/RPMS/i386/cwfc-$(FULLVERSION)-$(RELEASE).i386.rpm rpm/
	-cp /var/tmp/cwfc/SRPMS/cwfc-$(FULLVERSION)-$(RELEASE).src.rpm rpm/

