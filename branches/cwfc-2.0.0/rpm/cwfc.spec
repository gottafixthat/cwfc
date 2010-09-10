Summary: Cheetah Web Framework Classes
Name: cwfc
Version: {VERSION}
Release: {RELEASE}
License: Cheetah Information Systems and R. Marc Lewis
Group: Applications/Web
Source: cwfc-{VERSION}.tgz
Buildroot: /var/tmp/cwfc/root
Provides: cwfc
Obsoletes: awfc

%define _topdir /var/tmp/cwfc
%define prefix /usr/local/share/cwfc
%define zendenc /usr/local/Zend/bin/zendenc
%define phpenc /usr/local/bin/encode5
%define inst install

%description
Cheetah Web Framework Classes.  Building blocks and common libraries for building web applications.


%prep
%setup -q

#%build
#make RPM_OPT_FLAGS="$RPM_OPT_FLAGS"

%install
mkdir -p $RPM_BUILD_ROOT%{prefix}/smarty/internals
mkdir -p $RPM_BUILD_ROOT%{prefix}/smarty/plugins

# The main libraries

# Use this with SourceGuardian Encoder
# %{phpenc} -r -o $RPM_BUILD_ROOT%{prefix} \*.php

# Use this for the Zend Encoder
#for i in `find . -name \*.php -print`
#do
#%{zendenc} $i $RPM_BUILD_ROOT%{prefix}/$i
#done

# Use this for the "install" command
for i in `find . -name \*.php -print`
do
%{inst} -D $i $RPM_BUILD_ROOT%{prefix}/$i
done

%files
%defattr (-,apache,apache)
# Specifying the directories will include *all* files that we installed
# above.
/usr/local/share/cwfc

