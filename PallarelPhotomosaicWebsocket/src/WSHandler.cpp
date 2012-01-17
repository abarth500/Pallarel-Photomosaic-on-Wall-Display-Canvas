#include "WSHander.hpp"
#include "FindNearestNeighbor.hpp"
#include <boost/tokenizer.hpp>

using PallarelPhotomosaicWebsocket::WSHandler;
using namespace std;

void WSHandler::validate(websocketpp::session_ptr client) {}

void WSHandler::setFNN(PallarelPhotomosaicWebsocket::FindNearestNeighbor &fnnn){
	fnn = fnnn;
}

void WSHandler::on_message(websocketpp::session_ptr client, const std::string &msg) {
	typedef boost::escaped_list_separator<char> Separator;
	typedef boost::tokenizer<Separator, std::string::iterator> Tokenizer;
	Separator separator;
	stringstream ss;
	std::string readBuffer;
	int *tData = new int[fnn.dimension];
	char colorhex[2];
	string hex;
	int color;
	int nResult = 0;
	string nResultStr;
	string knnResultStr = "";
	bool first = true;
	ss<<msg;
	while(getline(ss,readBuffer)){
		if(!first){
			knnResultStr +=",";
		}
		first = false;
		Tokenizer tokens(readBuffer.begin(), readBuffer.end(), separator);
		int f = 0;
		cout<<"[]";
		for (Tokenizer::iterator tok_iter = tokens.begin(); tok_iter != tokens.end(); ++tok_iter) {
			
			cout<<"=>";
			switch(f++){			
			case 0:
				cout << "id\t";
				cout << *tok_iter;
				nResultStr = *tok_iter;
				nResult = atoi(nResultStr.c_str());
				break;
			case 1:
				//cout << "hex:\t" << *tok_iter << endl;
				hex = *tok_iter;
				for (int i = 0; i < fnn.dimension; i++) {
					colorhex[0] = hex.at(i*2);
					colorhex[1] = hex.at(i*2+1);
					color = strtoul(colorhex, NULL, 16);
					tData[i] = color;
				}
				knnResultStr += fnn.find(tData,1,nResult);
				cout << "))" <<endl;
				break;
			}
		}
	}
	//cout << knnResultStr;
	client->send(knnResultStr);
}

void WSHandler::on_message(websocketpp::session_ptr client, const std::vector<unsigned char> &data) {
	client->send(data);
}
