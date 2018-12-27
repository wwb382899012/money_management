#!/bin/sh

jmfPath="./jyb_microservice_framework"
corePath="./jmf_php_core"
machinePath="./machine_config"
agentPath="./jmf_agent"

rm -rf jyb_microservice_framework/
git clone http://reporter:Jyb12345@git.jtjr.com/microservice_framework_grp/jyb_microservice_framework.git jyb_microservice_framework
cd jyb_microservice_framework
find . -name ".git" | xargs rm -rf
cd ..

rm -rf jmf_php_core/
git clone -b liumin http://reporter:Jyb12345@git.jtjr.com/microservice_framework_grp/jmf_php_core.git jmf_php_core/1.0.0
cd jmf_php_core/1.0.0
find . -name ".git" | xargs rm -rf
cd ../..

rm -rf machine_config/
git clone http://reporter:Jyb12345@git.jtjr.com/microservice_framework_grp/machine_config.git machine_config
cd machine_config
find . -name ".git" | xargs rm -rf
cd ..

rm -rf jmf_agent/
git clone http://reporter:Jyb12345@git.jtjr.com/microservice_framework_grp/jmf_agent.git jmf_agent
cd jmf_agent
find . -name ".git" | xargs rm -rf
cd ..



